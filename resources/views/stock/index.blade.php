@extends('layouts.app')
@section('title', 'Stock Monitoring')
@section('page-title', 'Stock Monitoring')

@section('content')
    @php
        $isAdmin = auth()->user()->isAdmin();
        $typePill = ['stock_in' => 'pill-ok', 'stock_out' => 'pill-berry', 'damaged' => 'pill-danger'];
        $tabs = ['movements' => 'Movement History', 'stock' => 'Current Stock', 'low' => 'Low-Stock Products'];
    @endphp

    <div class="page-head">
        <div><h2 class="section-heading">Stock Monitoring</h2></div>
        <button type="button" class="btn btn-berry" data-bs-toggle="modal" data-bs-target="#movementModal"><i class="fa-solid fa-plus me-2"></i>Record Movement</button>
    </div>

    {{-- Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><x-stat-card icon="fa-arrow-down-long" label="Total Stock In" :value="number_format($totalIn)" hint="Includes opening stock" /></div>
        <div class="col-6 col-xl-3"><x-stat-card icon="fa-arrow-up-long" label="Total Stock Out" :value="number_format($totalOut)" hint="Removed, not sold" tone="rose" /></div>
        <div class="col-6 col-xl-3"><x-stat-card icon="fa-heart-crack" label="Total Damaged Items" :value="number_format($totalDamaged)" hint="Not sellable" tone="dark" /></div>
        <div class="col-6 col-xl-3"><x-stat-card icon="fa-triangle-exclamation" label="Low-Stock Products" :value="number_format($lowStockCount)" hint="At or below minimum" tone="warn" /></div>
    </div>

    {{-- Tabs --}}
    <ul class="tab-strip mb-3">
        @foreach ($tabs as $key => $label)
            <li><a href="{{ route('stock.index', ['view' => $key]) }}" class="{{ $view === $key ? 'active' : '' }}">{{ $label }}</a></li>
        @endforeach
    </ul>

    {{-- Filters --}}
    <form method="GET" action="{{ route('stock.index') }}" class="card-box filter-box mb-3">
        <input type="hidden" name="view" value="{{ $view }}">
        <div class="row g-2 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label small">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="{{ $view === 'movements' ? 'Product, SKU or remarks' : 'Product name or SKU' }}">
            </div>
            @if ($view === 'movements')
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small">Movement Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($typeLabels as $key => $label)<option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-6"><label class="form-label small">From</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm"></div>
                <div class="col-lg-2 col-6"><label class="form-label small">To</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm"></div>
            @endif
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-berry btn-sm px-3"><i class="fa-solid fa-magnifying-glass me-1"></i>Apply</button>
                <a href="{{ route('stock.index', ['view' => $view]) }}" class="btn btn-light btn-sm px-3">Reset</a>
            </div>
        </div>
    </form>

    <div class="card-box p-0 overflow-hidden">
        @if ($view === 'movements')
            @if ($movements->isEmpty())
                <div class="empty-state py-5"><i class="fa-solid fa-boxes-stacked"></i>No stock movements found.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-berry align-middle mb-0">
                        <thead><tr><th>Transaction ID</th><th>Product</th><th>Type</th><th class="text-end">Qty</th><th class="text-end">Previous</th><th class="text-end">Updated</th><th>User</th><th>Date</th><th>Remarks</th></tr></thead>
                        <tbody>
                        @foreach ($movements as $m)
                            <tr>
                                <td class="fw-medium text-nowrap">{{ $m->code }}</td>
                                <td>{{ $m->product?->name }}<br><small class="text-muted">{{ $m->product?->sku }}</small></td>
                                <td><span class="pill {{ $typePill[$m->type] ?? '' }}">{{ $m->type_label }}</span></td>
                                <td class="text-end fw-medium">{{ $m->type === 'stock_in' ? '+' : '-' }}{{ $m->quantity }}</td>
                                <td class="text-end">{{ $m->previous_stock }}</td>
                                <td class="text-end">{{ $m->updated_stock }}</td>
                                <td>{{ $m->user?->name ?? '-' }}</td>
                                <td class="text-nowrap">{{ $m->created_at->format('M d, Y h:i A') }}</td>
                                <td class="text-muted">{{ $m->remarks ?: '-' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($movements->hasPages())
                    <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">Showing {{ $movements->firstItem() }}-{{ $movements->lastItem() }} of {{ $movements->total() }}</small>
                        {{ $movements->links() }}
                    </div>
                @endif
            @endif
        @else
            @if ($products->isEmpty())
                <div class="empty-state py-5"><i class="fa-regular fa-circle-check"></i>
                    {{ $view === 'low' ? 'No products are low on stock.' : 'No products found.' }}
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-berry align-middle mb-0">
                        <thead><tr><th>Image</th><th>SKU</th><th>Product</th><th>Category</th><th class="text-end">Available</th><th class="text-end">Minimum</th><th>Status</th><th class="text-end">Record</th></tr></thead>
                        <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td>@include('inventory._thumb', ['product' => $product])</td>
                                <td class="text-nowrap">{{ $product->sku }}</td>
                                <td class="fw-medium">{{ $product->name }}<br><small class="text-muted fw-normal">{{ $product->size }} &middot; {{ $product->condition }}</small></td>
                                <td>{{ $product->category?->name }}</td>
                                <td class="text-end fw-medium">{{ $product->available_stock }}</td>
                                <td class="text-end">{{ $product->minimum_stock }}</td>
                                <td>@include('inventory._status', ['product' => $product])</td>
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#movementModal" data-type="stock_in" data-product="{{ $product->id }}">In</button>
                                    @if ($isAdmin)
                                        <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#movementModal" data-type="stock_out" data-product="{{ $product->id }}" @disabled($product->available_stock < 1)>Out</button>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#movementModal" data-type="damaged" data-product="{{ $product->id }}" @disabled($product->available_stock < 1)>Damaged</button>
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
        @endif
    </div>

    {{-- Record movement modal --}}
    <div class="modal fade" id="movementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('stock.store') }}" class="modal-content js-submit" style="border-radius:16px" data-confirm-dynamic>
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">Record Stock Movement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if ($errors->any() && session('open_modal'))
                        <div class="alert alert-danger py-2 small"><i class="fa-solid fa-circle-exclamation me-2"></i>{{ $errors->first() }}</div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label" for="mvType">Movement Type <span class="text-danger">*</span></label>
                        <select name="type" id="mvType" class="form-select" required>
                            @foreach ($allowedTypes as $t)
                                <option value="{{ $t }}" @selected(old('type', 'stock_in') === $t)>{{ $typeLabels[$t] }}</option>
                            @endforeach
                        </select>
                        <div class="form-text" id="mvTypeHelp"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="mvProduct">Product <span class="text-danger">*</span></label>
                        <select name="product_id" id="mvProduct" class="form-select" required>
                            <option value="">Select product</option>
                            @foreach ($productOptions as $p)
                                <option value="{{ $p->id }}" data-stock="{{ $p->available_stock }}" @selected(old('product_id') == $p->id)>{{ $p->name }} ({{ $p->sku }})</option>
                            @endforeach
                        </select>
                        <div class="form-text" id="mvStockHint">Select a product to see its available stock.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="mvQty">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="mvQty" min="1" max="100000" step="1" required value="{{ old('quantity', 1) }}" class="form-control">
                    </div>

                    <div>
                        <label class="form-label" for="mvRemarks">Remarks <span class="text-danger d-none" id="mvRemarksStar">*</span></label>
                        <textarea name="remarks" id="mvRemarks" rows="2" maxlength="255" class="form-control" placeholder="Optional for Stock In. Required for Stock Out and Damaged.">{{ old('remarks') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-berry px-4">Save Movement</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var modalEl = document.getElementById('movementModal');
    var typeSel = document.getElementById('mvType');
    var prodSel = document.getElementById('mvProduct');
    var qty = document.getElementById('mvQty');
    var remarks = document.getElementById('mvRemarks');
    var help = {
        stock_in: 'Adds new pieces to the available stock.',
        stock_out: 'Removes pieces for a reason other than a sale (admin only).',
        damaged: 'Removes damaged pieces that can no longer be sold.'
    };

    function refresh() {
        var type = typeSel.value;
        var opt = prodSel.options[prodSel.selectedIndex];
        var stock = opt && opt.dataset.stock !== undefined ? parseInt(opt.dataset.stock, 10) : null;

        document.getElementById('mvTypeHelp').textContent = help[type] || '';
        var needReason = type !== 'stock_in';
        document.getElementById('mvRemarksStar').classList.toggle('d-none', !needReason);
        remarks.required = needReason;

        var hint = document.getElementById('mvStockHint');
        if (stock === null) {
            hint.textContent = 'Select a product to see its available stock.';
            qty.removeAttribute('max');
        } else {
            hint.textContent = 'Available stock: ' + stock;
            if (type === 'stock_in') { qty.max = 100000; } else { qty.max = Math.max(stock, 1); }
        }
    }

    // Text for the confirmation dialog
    modalEl.querySelector('form').addEventListener('confirm:prepare', function () {
        var f = this, type = typeSel.value;
        var opt = prodSel.options[prodSel.selectedIndex];
        var q = parseInt(qty.value, 10) || 0;
        var stock = parseInt(opt && opt.dataset.stock, 10);
        var change = isNaN(stock) ? '' : ' Stock will change from ' + stock + ' to ' + (type === 'stock_in' ? stock + q : stock - q) + '.';
        var cfg = {
            stock_in: {t: 'Record stock in?', v: 'berry', i: 'fa-box-open', o: 'Yes, add stock', x: 'Add ' + q + ' piece(s) to this product.'},
            stock_out: {t: 'Record stock out?', v: 'warning', i: 'fa-arrow-right-from-bracket', o: 'Yes, remove stock', x: 'Remove ' + q + ' piece(s) from this product.'},
            damaged: {t: 'Record damaged items?', v: 'danger', i: 'fa-triangle-exclamation', o: 'Yes, record damage', x: 'Mark ' + q + ' piece(s) as damaged. They will no longer count as available stock.'}
        }[type] || {t: 'Save movement?', v: 'berry', i: 'fa-circle-question', o: 'Yes, save', x: 'This movement will be recorded.'};
        f.dataset.confirmTitle = cfg.t;
        f.dataset.confirmVariant = cfg.v;
        f.dataset.confirmIcon = cfg.i;
        f.dataset.confirmOk = cfg.o;
        f.dataset.confirmText = cfg.x + change;
        f.dataset.confirmName = opt ? opt.textContent.trim() : '';
    });

    typeSel.addEventListener('change', refresh);
    prodSel.addEventListener('change', refresh);

    modalEl.addEventListener('show.bs.modal', function (e) {
        var b = e.relatedTarget;
        if (b && b.dataset.type) {
            if (typeSel.querySelector('option[value="' + b.dataset.type + '"]')) { typeSel.value = b.dataset.type; }
            prodSel.value = b.dataset.product;
            qty.value = 1;
            remarks.value = '';
        }
        refresh();
    });

    refresh();

    @if ($errors->any() && session('open_modal'))
        new bootstrap.Modal(modalEl).show();
    @endif
})();
</script>
@endpush
