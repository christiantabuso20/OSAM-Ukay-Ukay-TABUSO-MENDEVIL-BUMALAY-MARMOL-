<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleRequest;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SalesController extends Controller
{
    /** Sales history. Admin sees every sale; staff see only the sales they recorded. */
    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));
        $from = $this->parseDate($request->query('date_from'));
        $to = $this->parseDate($request->query('date_to'));

        $base = Sale::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->when($user->isAdmin() && $request->filled('staff'), fn ($q) => $q->where('user_id', (int) $request->query('staff')))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('sale_number', 'like', "%{$search}%")
                    ->orWhereHas('items', fn ($i) => $i->where('product_name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
            }))
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to->endOfDay()));

        $count = (clone $base)->count();
        $totalSales = (float) (clone $base)->sum('total_amount');
        $itemsSold = (int) SaleItem::whereIn('sale_id', (clone $base)->select('id'))->sum('quantity');

        return view('sales.index', [
            'sales' => (clone $base)->with('items')->withSum('items as total_quantity', 'quantity')->latest()->latest('id')->paginate(10)->withQueryString(),
            'count' => $count,
            'totalSales' => $totalSales,
            'itemsSold' => $itemsSold,
            'average' => $count > 0 ? $totalSales / $count : 0,
            'staffOptions' => $user->isAdmin() ? User::orderBy('name')->get(['id', 'name']) : collect(),
        ]);
    }

    /** POS screen. */
    public function create(): View
    {
        // After a failed save, rebuild the cart from what was submitted (using current prices and stock).
        $oldCart = [];
        $old = collect(old('items', []))->filter(fn ($row) => is_array($row) && isset($row['product_id']));

        if ($old->isNotEmpty()) {
            $products = Product::whereIn('id', $old->pluck('product_id')->map(fn ($id) => (int) $id))->get()->keyBy('id');

            foreach ($old as $row) {
                $product = $products->get((int) $row['product_id']);
                if ($product && $product->available_stock > 0) {
                    $oldCart[] = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'price' => (float) $product->unit_price,
                        'stock' => (int) $product->available_stock,
                        'qty' => max(1, min((int) ($row['quantity'] ?? 1), (int) $product->available_stock)),
                    ];
                }
            }
        }

        return view('sales.create', [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'oldCart' => $oldCart,
        ]);
    }

    /** JSON product search used by the POS screen. */
    public function products(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $products = Product::with('category:id,name')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")))
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', (int) $request->query('category')))
            ->orderByRaw('available_stock > 0 desc')
            ->orderBy('name')
            ->limit(30)
            ->get();

        return response()->json($products->map(fn (Product $p) => [
            'id' => $p->id,
            'sku' => $p->sku,
            'name' => $p->name,
            'category' => $p->category?->name,
            'size' => $p->size,
            'condition' => $p->condition,
            'price' => (float) $p->unit_price,
            'stock' => (int) $p->available_stock,
            'image' => $p->image_url,
        ])->values());
    }

    public function store(SaleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Merge duplicate lines: product_id => quantity.
        $lines = collect($validated['items'])
            ->groupBy(fn ($row) => (int) $row['product_id'])
            ->map(fn ($rows) => (int) $rows->sum('quantity'));

        $paymentCents = (int) round(((float) $validated['payment_amount']) * 100);
        $staff = $request->user();

        $sale = DB::transaction(function () use ($lines, $paymentCents, $staff) {
            // Lock the products (in a fixed order) so two sales cannot take the same stock.
            $products = Product::whereIn('id', $lines->keys())->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            $problems = [];
            foreach ($lines as $productId => $qty) {
                $product = $products->get($productId);

                if (! $product) {
                    $problems[] = 'One of the selected products is no longer available.';
                } elseif ($qty > $product->available_stock) {
                    $problems[] = "Not enough stock for {$product->name}. Only {$product->available_stock} available.";
                }
            }
            if ($problems) {
                throw ValidationException::withMessages(['items' => $problems]);
            }

            // Prices always come from the database, never from the browser.
            $rows = [];
            $totalCents = 0;
            foreach ($lines as $productId => $qty) {
                $product = $products->get($productId);
                $priceCents = (int) round(((float) $product->unit_price) * 100);
                $subtotalCents = $priceCents * $qty;
                $totalCents += $subtotalCents;
                $rows[] = [$product, $qty, $priceCents, $subtotalCents];
            }

            if ($paymentCents < $totalCents) {
                throw ValidationException::withMessages([
                    'payment_amount' => 'The payment is less than the total amount of ₱'.number_format($totalCents / 100, 2).'.',
                ]);
            }

            $sale = null;
            for ($attempt = 1; $attempt <= 5; $attempt++) {
                try {
                    $sale = Sale::create([
                        'sale_number' => Sale::generateNumber(),
                        'user_id' => $staff->id,
                        'staff_name' => $staff->name,
                        'total_amount' => $this->money($totalCents),
                        'payment_amount' => $this->money($paymentCents),
                        'change_amount' => $this->money($paymentCents - $totalCents),
                    ]);
                    break;
                } catch (UniqueConstraintViolationException $e) {
                    if ($attempt === 5) {
                        throw $e; // another sale took the number every time; very unlikely
                    }
                }
            }

            foreach ($rows as [$product, $qty, $priceCents, $subtotalCents]) {
                $sale->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,   // snapshots keep history correct
                    'sku' => $product->sku,
                    'unit_price' => $this->money($priceCents),
                    'quantity' => $qty,
                    'subtotal' => $this->money($subtotalCents),
                ]);

                $product->update(['available_stock' => $product->available_stock - $qty]);
            }

            $itemCount = array_sum(array_column($rows, 1));
            ActivityLog::record('Sales', 'sale', "Sale {$sale->sale_number}: {$itemCount} item(s), total ₱".number_format($totalCents / 100, 2).'.');

            return $sale;
        });

        return redirect()->route('sales.receipt', $sale)
            ->with('success', "Sale {$sale->sale_number} completed. Change: ₱".number_format((float) $sale->change_amount, 2));
    }

    public function show(Request $request, Sale $sale): View
    {
        $this->authorizeSale($request, $sale);

        return view('sales.show', ['sale' => $sale->load('items.product', 'user')]);
    }

    public function receipt(Request $request, Sale $sale): View
    {
        $this->authorizeSale($request, $sale);

        return view('sales.receipt', ['sale' => $sale->load('items')]);
    }

    /** Staff can only open their own sales. */
    private function authorizeSale(Request $request, Sale $sale): void
    {
        $user = $request->user();

        if (! $user->isAdmin() && $sale->user_id !== $user->id) {
            abort(403, 'You can only view sales that you recorded.');
        }
    }

    private function money(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function parseDate(?string $value): ?CarbonImmutable
    {
        if (! $value) {
            return null;
        }

        return rescue(fn () => CarbonImmutable::createFromFormat('Y-m-d', $value), null, false) ?: null;
    }
}
