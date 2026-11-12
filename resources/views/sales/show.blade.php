@extends('layouts.app')
@section('title', $sale->sale_number)
@section('page-title', 'Sales Recording')

@section('content')
    <div class="page-head">
        <div><h2 class="section-heading">{{ $sale->sale_number }}</h2><p class="text-muted mb-0">{{ $sale->created_at->format('F d, Y h:i A') }}</p></div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('sales.index') }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-2"></i>Back</a>
            <a href="{{ route('sales.receipt', ['sale' => $sale, 'print' => 1]) }}" class="btn btn-berry"><i class="fa-solid fa-print me-2"></i>Print Receipt</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card-box p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-berry align-middle mb-0">
                        <thead><tr><th>Product</th><th>SKU</th><th class="text-end">Unit Price</th><th class="text-end">Qty</th><th class="text-end">Subtotal</th></tr></thead>
                        <tbody>
                        @foreach ($sale->items as $item)
                            <tr>
                                <td class="fw-medium">
                                    {{ $item->product_name }}
                                    @if ($item->product?->trashed())<span class="pill pill-warn ms-1">Archived</span>@endif
                                </td>
                                <td class="text-nowrap">{{ $item->sku }}</td>
                                <td class="text-end text-nowrap">₱{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end">{{ $item->quantity }}</td>
                                <td class="text-end fw-medium text-nowrap">₱{{ number_format($item->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-box">
                <div class="card-box-header"><h3>Transaction Summary</h3></div>
                <dl class="detail-grid" style="grid-template-columns:1fr">
                    <div><dt>Total Amount</dt><dd class="fs-5">₱{{ number_format($sale->total_amount, 2) }}</dd></div>
                    <div><dt>Payment Amount</dt><dd>₱{{ number_format($sale->payment_amount, 2) }}</dd></div>
                    <div><dt>Change</dt><dd>₱{{ number_format($sale->change_amount, 2) }}</dd></div>
                    <div><dt>Items Sold</dt><dd>{{ $sale->items->sum('quantity') }}</dd></div>
                    <div><dt>Staff Responsible</dt><dd>{{ $sale->staff_name }}</dd></div>
                </dl>
            </div>
        </div>
    </div>
@endsection
