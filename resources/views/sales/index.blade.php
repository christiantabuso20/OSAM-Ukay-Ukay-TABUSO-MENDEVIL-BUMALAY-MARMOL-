@extends('layouts.app')
@section('title', 'Sales Recording')
@section('page-title', 'Sales Recording')

@section('content')
    @php $isAdmin = auth()->user()->isAdmin(); @endphp

    <div class="page-head">
        <div>
            <h2 class="section-heading">Sales History</h2>
        </div>
        <a href="{{ route('sales.create') }}" class="btn btn-berry"><i class="fa-solid fa-cash-register me-2"></i>New Sale</a>
    </div>

    {{-- Summary (follows the filters) --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><x-stat-card icon="fa-receipt" label="Transactions" :value="number_format($count)" hint="Matching the filters" /></div>
        <div class="col-6 col-xl-3"><x-stat-card icon="fa-peso-sign" label="Total Sales" :value="'₱'.number_format($totalSales, 2)" hint="Sum of totals" tone="rose" /></div>
        <div class="col-6 col-xl-3"><x-stat-card icon="fa-shirt" label="Items Sold" :value="number_format($itemsSold)" hint="Total quantity" tone="dark" /></div>
        <div class="col-6 col-xl-3"><x-stat-card icon="fa-chart-simple" label="Average Sale" :value="'₱'.number_format($average, 2)" hint="Per transaction" tone="warn" /></div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('sales.index') }}" class="card-box filter-box mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label small">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Sales ID, product name or SKU">
            </div>
            @if ($isAdmin)
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small">Staff</label>
                    <select name="staff" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($staffOptions as $s)<option value="{{ $s->id }}" @selected(request('staff') == $s->id)>{{ $s->name }}</option>@endforeach
                    </select>
                </div>
            @endif
            <div class="col-lg-2 col-6"><label class="form-label small">From</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm"></div>
            <div class="col-lg-2 col-6"><label class="form-label small">To</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm"></div>
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-berry btn-sm px-3"><i class="fa-solid fa-magnifying-glass me-1"></i>Apply</button>
                <a href="{{ route('sales.index') }}" class="btn btn-light btn-sm px-3">Reset</a>
            </div>
        </div>
    </form>

    <div class="card-box p-0 overflow-hidden">
        @if ($sales->isEmpty())
            <div class="empty-state py-5"><i class="fa-solid fa-receipt"></i>No sales found.</div>
        @else
            <div class="table-responsive">
                <table class="table table-berry align-middle mb-0">
                    <thead><tr><th>Sales ID</th><th>Date</th><th>Product(s)</th><th class="text-end">Qty</th><th class="text-end">Total</th><th class="text-end">Payment</th><th class="text-end">Change</th><th>Staff</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    @foreach ($sales as $sale)
                        @php $first = $sale->items->first(); $more = $sale->items->count() - 1; @endphp
                        <tr>
                            <td class="fw-medium text-nowrap">{{ $sale->sale_number }}</td>
                            <td class="text-nowrap">{{ $sale->created_at->format('M d, Y h:i A') }}</td>
                            <td>{{ $first?->product_name }} @if ($more > 0)<small class="text-muted">+{{ $more }} more</small>@endif</td>
                            <td class="text-end">{{ $sale->total_quantity }}</td>
                            <td class="text-end fw-medium text-nowrap">₱{{ number_format($sale->total_amount, 2) }}</td>
                            <td class="text-end text-nowrap">₱{{ number_format($sale->payment_amount, 2) }}</td>
                            <td class="text-end text-nowrap">₱{{ number_format($sale->change_amount, 2) }}</td>
                            <td>{{ $sale->staff_name }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('sales.show', $sale) }}" class="btn-action" title="View details"><i class="fa-regular fa-eye"></i></a>
                                <a href="{{ route('sales.receipt', $sale) }}" class="btn-action" title="Receipt"><i class="fa-solid fa-print"></i></a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($sales->hasPages())
                <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">Showing {{ $sales->firstItem() }}-{{ $sales->lastItem() }} of {{ $sales->total() }}</small>
                    {{ $sales->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
