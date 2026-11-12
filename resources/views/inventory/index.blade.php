@extends('layouts.app')
@section('title', 'Clothing Inventory')
@section('page-title', 'Clothing Inventory')

@section('content')
    @php $isAdmin = auth()->user()->isAdmin(); @endphp

    <div class="page-head">
        <div><h2 class="section-heading">Clothing Inventory</h2></div>
        @if ($isAdmin)
            <a href="{{ route('products.create') }}" class="btn btn-berry"><i class="fa-solid fa-plus me-2"></i>Add Product</a>
        @endif
    </div>

    {{-- Search, filters, sort --}}
    <form method="GET" action="{{ route('products.index') }}" class="card-box filter-box mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-lg-3 col-md-6">
                <label class="form-label small">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Name, SKU or category">
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label small">Category</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-lg-1 col-md-4 col-6">
                <label class="form-label small">Size</label>
                <select name="size" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach ($sizes as $s)<option value="{{ $s }}" @selected(request('size') === $s)>{{ $s }}</option>@endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <label class="form-label small">Condition</label>
                <select name="condition" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach ($conditions as $c)<option value="{{ $c }}" @selected(request('condition') === $c)>{{ $c }}</option>@endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-4">
                <label class="form-label small">Stock Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="in_stock" @selected(request('status') === 'in_stock')>In Stock</option>
                    <option value="low_stock" @selected(request('status') === 'low_stock')>Low Stock</option>
                    <option value="out_of_stock" @selected(request('status') === 'out_of_stock')>Out of Stock</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label small">Sort by</label>
                <select name="sort" class="form-select form-select-sm">
                    @foreach ($sortOptions as $key => $label)<option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button class="btn btn-berry btn-sm px-3"><i class="fa-solid fa-magnifying-glass me-1"></i>Apply</button>
            <a href="{{ route('products.index') }}" class="btn btn-light btn-sm px-3">Reset</a>
        </div>
    </form>

    <div class="card-box p-0 overflow-hidden">
        @if ($products->isEmpty())
            <div class="empty-state py-5"><i class="fa-solid fa-shirt"></i>
                @if (request()->hasAny(['search', 'category', 'size', 'condition', 'status']))
                    No products match your search or filters.
                @else
                    No products yet.@if ($isAdmin) Click "Add Product" to register your first clothing item.@endif
                @endif
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-berry align-middle mb-0">
                    <thead>
                        <tr><th>Image</th><th>SKU</th><th>Product Name</th><th>Category</th><th>Size</th><th>Condition</th><th class="text-end">Price</th><th class="text-end">Stock</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td>@include('inventory._thumb', ['product' => $product])</td>
                            <td class="text-nowrap">{{ $product->sku }}</td>
                            <td class="fw-medium">{{ $product->name }}</td>
                            <td>{{ $product->category?->name }}</td>
                            <td>{{ $product->size }}</td>
                            <td>{{ $product->condition }}</td>
                            <td class="text-end text-nowrap">₱{{ number_format($product->unit_price, 2) }}</td>
                            <td class="text-end fw-medium">{{ $product->available_stock }}</td>
                            <td>@include('inventory._status', ['product' => $product])</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('products.show', ['product' => $product, 'return_to' => url()->full()]) }}" class="btn-action" title="View"><i class="fa-regular fa-eye"></i></a>
                                @if ($isAdmin)
                                    <a href="{{ route('products.edit', $product) }}" class="btn-action" title="Edit"><i class="fa-regular fa-pen-to-square"></i></a>
                                    <form method="POST" action="{{ route('products.destroy', $product) }}" class="d-inline"
                                          data-confirm-title="Delete product?" data-confirm-text="This product will be removed from the inventory. Its sales and stock history will be kept."
                                          data-confirm-name="{{ $product->name }}" data-confirm-ok="Yes, delete" data-confirm-variant="danger" data-confirm-icon="fa-trash-can">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-action danger" title="Delete"><i class="fa-regular fa-trash-can"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($products->hasPages())
                <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">Showing {{ $products->firstItem() }}-{{ $products->lastItem() }} of {{ $products->total() }}</small>
                    {{ $products->links() }}
                </div>
            @endif
        @endif
    </div>

@endsection
