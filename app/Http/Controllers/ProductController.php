<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    private const SORTS = [
        'latest' => ['created_at', 'desc'],
        'oldest' => ['created_at', 'asc'],
        'name_asc' => ['name', 'asc'],
        'name_desc' => ['name', 'desc'],
        'price_asc' => ['unit_price', 'asc'],
        'price_desc' => ['unit_price', 'desc'],
        'stock_asc' => ['available_stock', 'asc'],
        'stock_desc' => ['available_stock', 'desc'],
    ];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $sort = array_key_exists($request->query('sort'), self::SORTS) ? $request->query('sort') : 'latest';
        [$sortColumn, $sortDir] = self::SORTS[$sort];

        $products = Product::with('category')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->query('category')))
            ->when(in_array($request->query('size'), Product::SIZES, true), fn ($q) => $q->where('size', $request->query('size')))
            ->when(in_array($request->query('condition'), Product::CONDITIONS, true), fn ($q) => $q->where('condition', $request->query('condition')))
            ->when($request->query('status'), function ($q, $status) {
                match ($status) {
                    'in_stock' => $q->whereColumn('available_stock', '>', 'minimum_stock'),
                    'low_stock' => $q->where('available_stock', '>', 0)->whereColumn('available_stock', '<=', 'minimum_stock'),
                    'out_of_stock' => $q->where('available_stock', '<=', 0),
                    default => null,
                };
            })
            ->orderBy($sortColumn, $sortDir)
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('inventory.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
            'sizes' => Product::SIZES,
            'conditions' => Product::CONDITIONS,
            'sortOptions' => [
                'latest' => 'Newest first', 'oldest' => 'Oldest first',
                'name_asc' => 'Name (A-Z)', 'name_desc' => 'Name (Z-A)',
                'price_asc' => 'Price (low to high)', 'price_desc' => 'Price (high to low)',
                'stock_asc' => 'Stock (low to high)', 'stock_desc' => 'Stock (high to low)',
            ],
            'sort' => $sort,
        ]);
    }

    public function create(): View
    {
        return view('inventory.create', $this->formData() + ['product' => new Product()]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $imagePath = $request->hasFile('image') ? $request->file('image')->store('products', 'public') : null;

        try {
            $product = DB::transaction(function () use ($data, $imagePath, $request) {
                $product = Product::create($data + [
                    'sku' => Product::generateSku(),
                    'available_stock' => $data['initial_stock'],
                    'image_path' => $imagePath,
                ]);

                // Opening stock is recorded in the stock history.
                if ($product->initial_stock > 0) {
                    StockMovement::create([
                        'product_id' => $product->id,
                        'user_id' => $request->user()->id,
                        'type' => StockMovement::TYPE_IN,
                        'quantity' => $product->initial_stock,
                        'previous_stock' => 0,
                        'updated_stock' => $product->initial_stock,
                        'remarks' => 'Initial stock',
                    ]);
                }

                ActivityLog::record('Inventory', 'create', "Added product {$product->name} ({$product->sku}).");

                return $product;
            });
        } catch (\Throwable $e) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            report($e);

            return back()->withInput()->with('error', 'The product could not be saved. Please try again.');
        }

        return redirect()->route('products.show', $product)->with('success', "Product {$product->name} was added.");
    }

    public function show(Product $product): View
    {
        $product->load('category');

        return view('inventory.show', [
            'product' => $product,
            'movements' => $product->stockMovements()->with('user')->latest()->limit(10)->get(),
            'soldQuantity' => (int) $product->saleItems()->sum('quantity'),
        ]);
    }

    public function edit(Product $product): View
    {
        return view('inventory.edit', $this->formData() + ['product' => $product]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $oldImage = $product->image_path;
        $newImage = $request->hasFile('image') ? $request->file('image')->store('products', 'public') : null;

        try {
            DB::transaction(function () use ($product, $data, $newImage) {
                $product->fill($data);
                if ($newImage) {
                    $product->image_path = $newImage;
                }
                $product->save();

                ActivityLog::record('Inventory', 'update', "Updated product {$product->name} ({$product->sku}).");
            });
        } catch (\Throwable $e) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }
            report($e);

            return back()->withInput()->with('error', 'The product could not be updated. Please try again.');
        }

        if ($newImage && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('products.show', $product)->with('success', "Product {$product->name} was updated.");
    }

    /** Soft delete: the row stays in the database so sales and stock history are preserved. */
    public function destroy(Product $product): RedirectResponse
    {
        DB::transaction(function () use ($product) {
            $product->delete();
            ActivityLog::record('Inventory', 'delete', "Deleted product {$product->name} ({$product->sku}).");
        });

        return redirect()->route('products.index')->with('success', "Product {$product->name} was deleted.");
    }

    private function formData(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(),
            'sizes' => Product::SIZES,
            'conditions' => Product::CONDITIONS,
            'clothingTypes' => Product::CLOTHING_TYPES,
        ];
    }
}
