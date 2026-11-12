@extends('layouts.app')
@section('title', $product->name)
@section('page-title', 'Clothing Inventory')

@section('content')
    @php $isAdmin = auth()->user()->isAdmin(); @endphp

    <div class="page-head">
        <div><h2 class="section-heading">{{ $product->name }}</h2><p class="text-muted mb-0">{{ $product->sku }}</p></div>
        <div class="d-flex gap-2">
            <a href="{{ request('return_to', route('products.index')) }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-2"></i>Back</a>
            @if ($isAdmin)
                <a href="{{ route('products.edit', $product) }}" class="btn btn-berry"><i class="fa-regular fa-pen-to-square me-2"></i>Edit</a>
                <form method="POST" action="{{ route('products.destroy', $product) }}"
                      data-confirm-title="Delete product?" data-confirm-text="This product will be removed from the inventory. Its sales and stock history will be kept."
                      data-confirm-name="{{ $product->name }}" data-confirm-ok="Yes, delete" data-confirm-variant="danger" data-confirm-icon="fa-trash-can">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="fa-regular fa-trash-can me-2"></i>Delete</button>
                </form>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card-box text-center">
                @if ($product->image_url)
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="detail-img">
                @else
                    <div class="preview-empty"><i class="fa-solid fa-shirt"></i><div>No image</div></div>
                @endif
                <div class="mt-3">@include('inventory._status', ['product' => $product])</div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card-box mb-4">
                <div class="card-box-header"><h3>Product Details</h3></div>
                <dl class="detail-grid">
                    <div><dt>Category</dt><dd>{{ $product->category?->name }}</dd></div>
                    <div><dt>Clothing Type</dt><dd>{{ $product->clothing_type }}</dd></div>
                    <div><dt>Size</dt><dd>{{ $product->size }}</dd></div>
                    <div><dt>Condition</dt><dd>{{ $product->condition }}</dd></div>
                    <div><dt>Unit Price</dt><dd>₱{{ number_format($product->unit_price, 2) }}</dd></div>
                    <div><dt>Date Added</dt><dd>{{ $product->created_at->format('M d, Y h:i A') }}</dd></div>
                    <div><dt>Initial Stock</dt><dd>{{ $product->initial_stock }}</dd></div>
                    <div><dt>Available Stock</dt><dd>{{ $product->available_stock }}</dd></div>
                    <div><dt>Minimum Stock Level</dt><dd>{{ $product->minimum_stock }}</dd></div>
                    <div><dt>Total Sold</dt><dd>{{ $soldQuantity }}</dd></div>
                </dl>
                <div class="mt-3">
                    <div class="small text-muted">Description</div>
                    <div>{{ $product->description ?: 'No description provided.' }}</div>
                </div>
            </div>

            <div class="card-box">
                <div class="card-box-header"><h3>Recent Stock Movements</h3></div>
                @if ($movements->isEmpty())
                    <div class="empty-state"><i class="fa-solid fa-boxes-stacked"></i>No stock movements yet.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-berry align-middle mb-0">
                            <thead><tr><th>Date</th><th>Type</th><th class="text-end">Qty</th><th class="text-end">Before</th><th class="text-end">After</th><th>By</th><th>Remarks</th></tr></thead>
                            <tbody>
                            @foreach ($movements as $m)
                                <tr>
                                    <td class="text-nowrap">{{ $m->created_at->format('M d, Y h:i A') }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $m->type)) }}</td>
                                    <td class="text-end">{{ $m->quantity }}</td>
                                    <td class="text-end">{{ $m->previous_stock }}</td>
                                    <td class="text-end">{{ $m->updated_stock }}</td>
                                    <td>{{ $m->user?->name ?? '-' }}</td>
                                    <td>{{ $m->remarks }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
