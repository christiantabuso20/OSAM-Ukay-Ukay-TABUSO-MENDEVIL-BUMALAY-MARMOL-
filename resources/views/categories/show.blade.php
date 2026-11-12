@extends('layouts.app')
@section('title', $category->name)
@section('page-title', 'Category Management')

@section('content')
    <div class="page-head">
        <div><h2 class="section-heading">{{ $category->name }}</h2><p class="text-muted mb-0">{{ $category->description ?: 'No description provided.' }}</p></div>
        <div class="d-flex gap-2">
            <a href="{{ route('categories.index') }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-2"></i>Back</a>
            <a href="{{ route('products.index', ['category' => $category->id]) }}" class="btn btn-berry"><i class="fa-solid fa-shirt me-2"></i>Open in Inventory</a>
        </div>
    </div>

    <div class="card-box p-0 overflow-hidden">
        <div class="p-3 border-bottom"><strong>Products in this category</strong> <span class="text-muted">({{ $products->total() }})</span></div>
        @if ($products->isEmpty())
            <div class="empty-state py-5"><i class="fa-solid fa-shirt"></i>No products use this category yet.</div>
        @else
            <div class="table-responsive">
                <table class="table table-berry align-middle mb-0">
                    <thead><tr><th>Image</th><th>SKU</th><th>Product Name</th><th>Size</th><th>Condition</th><th class="text-end">Price</th><th class="text-end">Stock</th><th>Status</th><th class="text-end">View</th></tr></thead>
                    <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td>@include('inventory._thumb', ['product' => $product])</td>
                            <td class="text-nowrap">{{ $product->sku }}</td>
                            <td class="fw-medium">{{ $product->name }}</td>
                            <td>{{ $product->size }}</td>
                            <td>{{ $product->condition }}</td>
                            <td class="text-end text-nowrap">₱{{ number_format($product->unit_price, 2) }}</td>
                            <td class="text-end fw-medium">{{ $product->available_stock }}</td>
                            <td>@include('inventory._status', ['product' => $product])</td>
                            <td class="text-end"><a href="{{ route('products.show', $product) }}" class="btn-action" title="View"><i class="fa-regular fa-eye"></i></a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($products->hasPages())
                <div class="p-3 border-top">{{ $products->links() }}</div>
            @endif
        @endif
    </div>
@endsection
