<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockMovementRequest;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\StockMovement;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    /** view = movements (history) | stock (current quantities) | low (low-stock products) */
    public function index(Request $request): View
    {
        $view = in_array($request->query('view'), ['movements', 'stock', 'low'], true) ? $request->query('view') : 'movements';
        $search = trim((string) $request->query('search', ''));

        $data = [
            'view' => $view,
            'totalIn' => (int) StockMovement::where('type', StockMovement::TYPE_IN)->sum('quantity'),
            'totalOut' => (int) StockMovement::where('type', StockMovement::TYPE_OUT)->sum('quantity'),
            'totalDamaged' => (int) StockMovement::where('type', StockMovement::TYPE_DAMAGED)->sum('quantity'),
            'lowStockCount' => Product::lowStock()->count(),
            'allowedTypes' => StockMovement::allowedTypesFor($request->user()),
            'typeLabels' => StockMovement::TYPES,
            'productOptions' => Product::orderBy('name')->get(['id', 'sku', 'name', 'available_stock']),
        ];

        if ($view === 'movements') {
            $from = $this->parseDate($request->query('date_from'));
            $to = $this->parseDate($request->query('date_to'));

            $data['movements'] = StockMovement::with(['product', 'user'])
                ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                    $q->whereHas('product', fn ($p) => $p->withTrashed()->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                        ->orWhere('remarks', 'like', "%{$search}%");
                }))
                ->when(array_key_exists($request->query('type'), StockMovement::TYPES), fn ($q) => $q->where('type', $request->query('type')))
                ->when($from, fn ($q) => $q->where('created_at', '>=', $from->startOfDay()))
                ->when($to, fn ($q) => $q->where('created_at', '<=', $to->endOfDay()))
                ->latest()->latest('id')
                ->paginate(10)->withQueryString();
        } else {
            $data['products'] = Product::with('category')
                ->when($view === 'low', fn ($q) => $q->lowStock())
                ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")))
                ->orderBy('available_stock')->orderBy('name')
                ->paginate(10)->withQueryString();
        }

        return view('stock.index', $data);
    }

    public function store(StockMovementRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $movement = DB::transaction(function () use ($validated, $request) {
            // Lock the row so two people cannot change the same stock at the same moment.
            $product = Product::lockForUpdate()->findOrFail($validated['product_id']);

            $previous = (int) $product->available_stock;
            $qty = (int) $validated['quantity'];
            $updated = $validated['type'] === StockMovement::TYPE_IN ? $previous + $qty : $previous - $qty;

            if ($updated < 0) {
                session()->flash('open_modal', true);
                throw ValidationException::withMessages([
                    'quantity' => "Not enough stock. {$product->name} only has {$previous} available.",
                ]);
            }

            $product->update(['available_stock' => $updated]);

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
                'type' => $validated['type'],
                'quantity' => $qty,
                'previous_stock' => $previous,
                'updated_stock' => $updated,
                'remarks' => $validated['remarks'] ?: null,
            ]);

            $label = StockMovement::TYPES[$validated['type']];
            ActivityLog::record('Stock', $validated['type'], "{$label}: {$qty} x {$product->name} ({$product->sku}), stock {$previous} to {$updated}.");

            return $movement->load('product');
        });

        return redirect()->route('stock.index')
            ->with('success', "{$movement->type_label} recorded for {$movement->product->name}. New stock: {$movement->updated_stock}.");
    }

    private function parseDate(?string $value): ?CarbonImmutable
    {
        if (! $value) {
            return null;
        }

        return rescue(fn () => CarbonImmutable::createFromFormat('Y-m-d', $value), null, false) ?: null;
    }
}
