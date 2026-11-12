@extends('layouts.app')
@section('title', 'New Sale')
@section('page-title', 'Sales Recording')

@section('content')
    <div class="page-head">
        <div><h2 class="section-heading">New Sale</h2></div>
        <a href="{{ route('sales.index') }}" class="btn btn-light"><i class="fa-solid fa-list me-2"></i>Sales History</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation me-2"></i>The sale was not saved:
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        {{-- Products --}}
        <div class="col-xl-7">
            <div class="card-box">
                <div class="row g-2 mb-3">
                    <div class="col-md-8">
                        <div class="input-group input-group-sm pos-search">
                            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" id="posSearch" class="form-control" placeholder="Search by product name or SKU" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select id="posCategory" class="form-select form-select-sm">
                            <option value="">All categories</option>
                            @foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div id="posProducts" class="pos-grid"></div>
                <small class="text-muted d-block mt-3" id="posHint">Showing up to 30 products. Use the search to find others.</small>
            </div>
        </div>

        {{-- Cart --}}
        <div class="col-xl-5">
            <form method="POST" action="{{ route('sales.store') }}" id="saleForm" class="card-box pos-cart js-submit" data-confirm-dynamic>
                @csrf
                <div id="cartInputs"></div>

                <div class="card-box-header"><h3><i class="fa-solid fa-cart-shopping me-2"></i>Current Sale</h3><button type="button" class="btn btn-light btn-sm" id="clearCart">Clear</button></div>

                <div id="cartList" class="pos-cart-list"></div>

                <div class="pos-totals">
                    <div class="d-flex justify-content-between"><span class="text-muted">Items</span><strong id="cartCount">0</strong></div>
                    <div class="d-flex justify-content-between align-items-center pos-total-row"><span>Total Amount</span><strong id="cartTotal">₱0.00</strong></div>
                </div>

                <div class="mt-3">
                    <label for="payment" class="form-label">Payment Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input type="number" name="payment_amount" id="payment" min="0" step="0.01" inputmode="decimal" required value="{{ old('payment_amount') }}" class="form-control form-control-lg" placeholder="0.00">
                    </div>
                    <div class="d-flex gap-2 flex-wrap mt-2" id="quickPay">
                        <button type="button" class="btn btn-light btn-sm" data-exact>Exact</button>
                        <button type="button" class="btn btn-light btn-sm" data-add="100">+₱100</button>
                        <button type="button" class="btn btn-light btn-sm" data-add="500">+₱500</button>
                        <button type="button" class="btn btn-light btn-sm" data-add="1000">+₱1,000</button>
                    </div>
                    <div class="pos-change mt-3"><span>Change</span><strong id="cartChange">₱0.00</strong></div>
                    <div class="small text-danger mt-1 d-none" id="payWarn"></div>
                </div>

                <button type="submit" class="btn btn-berry w-100 mt-3 py-2" id="completeBtn" disabled><i class="fa-solid fa-check me-2"></i>Complete Sale</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var productsUrl = @json(route('sales.products'));
    var cart = @json($oldCart); // [{id, name, sku, price, stock, qty}]

    var grid = document.getElementById('posProducts');
    var searchEl = document.getElementById('posSearch');
    var catEl = document.getElementById('posCategory');
    var form = document.getElementById('saleForm');
    var cartList = document.getElementById('cartList');
    var inputsBox = document.getElementById('cartInputs');
    var payment = document.getElementById('payment');
    var completeBtn = document.getElementById('completeBtn');
    var warn = document.getElementById('payWarn');

    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function cents(n) { return Math.round(parseFloat(n || 0) * 100); }
    function peso(c) { return '₱' + (c / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function findLine(id) { return cart.find(function (l) { return l.id === id; }); }
    function totalCents() { return cart.reduce(function (sum, l) { return sum + cents(l.price) * l.qty; }, 0); }

    /* ---------- Product list ---------- */
    var timer = null, lastQuery = 0;
    function loadProducts() {
        var token = ++lastQuery;
        var url = productsUrl + '?q=' + encodeURIComponent(searchEl.value.trim()) + '&category=' + encodeURIComponent(catEl.value);
        grid.innerHTML = '<div class="empty-state w-100"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</div>';
        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) { throw new Error('bad'); } return r.json(); })
            .then(function (list) { if (token === lastQuery) { renderProducts(list); } })
            .catch(function () { if (token === lastQuery) { grid.innerHTML = '<div class="empty-state w-100"><i class="fa-solid fa-triangle-exclamation"></i>Could not load products. Please refresh the page.</div>'; } });
    }

    function renderProducts(list) {
        if (!list.length) {
            grid.innerHTML = '<div class="empty-state w-100"><i class="fa-solid fa-shirt"></i>No products found.</div>';
            return;
        }
        grid.innerHTML = list.map(function (p) {
            var out = p.stock < 1;
            var pill = out ? 'pill-danger' : (p.stock <= 3 ? 'pill-warn' : 'pill-ok');
            var thumb = p.image ? '<img src="' + esc(p.image) + '" alt="">' : '<i class="fa-solid fa-shirt"></i>';
            return '<button type="button" class="pos-product" data-id="' + p.id + '"' + (out ? ' disabled' : '') + '>' +
                '<span class="pos-thumb">' + thumb + '</span>' +
                '<span class="pos-info"><span class="pos-name">' + esc(p.name) + '</span>' +
                '<small class="text-muted d-block">' + esc(p.sku) + ' &middot; ' + esc(p.size) + ' &middot; ' + esc(p.condition) + '</small>' +
                '<span class="pos-meta"><strong>' + peso(cents(p.price)) + '</strong><span class="pill ' + pill + '">' + (out ? 'Out of stock' : p.stock + ' left') + '</span></span></span></button>';
        }).join('');
        list.forEach(function (p) { grid.querySelector('[data-id="' + p.id + '"]').__product = p; });
    }

    grid.addEventListener('click', function (e) {
        var btn = e.target.closest('.pos-product');
        if (!btn || btn.disabled) { return; }
        addToCart(btn.__product);
    });
    searchEl.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(loadProducts, 250); });
    catEl.addEventListener('change', loadProducts);

    /* ---------- Cart ---------- */
    function addToCart(p) {
        var line = findLine(p.id);
        if (line) {
            line.stock = p.stock;
            if (line.qty >= line.stock) { flash('Only ' + line.stock + ' of ' + p.name + ' available.'); return; }
            line.qty++;
        } else {
            cart.push({ id: p.id, name: p.name, sku: p.sku, price: p.price, stock: p.stock, qty: 1 });
        }
        renderCart();
    }

    var flashMsg = '', flashTimer = null;
    function flash(msg) {
        flashMsg = msg;
        clearTimeout(flashTimer);
        flashTimer = setTimeout(function () { flashMsg = ''; updateTotals(); }, 2500);
        updateTotals();
    }

    function renderCart() {
        if (!cart.length) {
            cartList.innerHTML = '<div class="empty-state"><i class="fa-solid fa-cart-shopping"></i>No items yet. Click a product to add it.</div>';
        } else {
            cartList.innerHTML = cart.map(function (l, i) {
                return '<div class="cart-line" data-i="' + i + '">' +
                    '<div class="cart-line-top"><div><div class="fw-medium">' + esc(l.name) + '</div><small class="text-muted">' + esc(l.sku) + ' &middot; ' + peso(cents(l.price)) + ' each &middot; ' + l.stock + ' in stock</small></div>' +
                    '<button type="button" class="btn-action danger" data-remove title="Remove"><i class="fa-regular fa-trash-can"></i></button></div>' +
                    '<div class="cart-line-bottom"><div class="qty-stepper"><button type="button" data-dec aria-label="Decrease">&minus;</button>' +
                    '<input type="number" min="1" max="' + l.stock + '" value="' + l.qty + '" data-qty aria-label="Quantity"><button type="button" data-inc aria-label="Increase">+</button></div>' +
                    '<strong>' + peso(cents(l.price) * l.qty) + '</strong></div></div>';
            }).join('');
        }

        // Hidden inputs posted with the form
        inputsBox.innerHTML = cart.map(function (l, i) {
            return '<input type="hidden" name="items[' + i + '][product_id]" value="' + l.id + '">' +
                   '<input type="hidden" name="items[' + i + '][quantity]" value="' + l.qty + '">';
        }).join('');
        updateTotals();
    }

    cartList.addEventListener('click', function (e) {
        var row = e.target.closest('.cart-line'); if (!row) { return; }
        var line = cart[parseInt(row.dataset.i, 10)];
        if (e.target.closest('[data-remove]')) { cart.splice(cart.indexOf(line), 1); }
        else if (e.target.closest('[data-inc]')) { if (line.qty < line.stock) { line.qty++; } else { renderCart(); flash('Only ' + line.stock + ' available.'); return; } }
        else if (e.target.closest('[data-dec]')) { if (line.qty > 1) { line.qty--; } }
        else { return; }
        renderCart();
    });
    cartList.addEventListener('change', function (e) {
        if (!e.target.matches('[data-qty]')) { return; }
        var line = cart[parseInt(e.target.closest('.cart-line').dataset.i, 10)];
        var q = parseInt(e.target.value, 10);
        if (isNaN(q) || q < 1) { q = 1; }
        var capped = q > line.stock;
        if (capped) { q = line.stock; }
        line.qty = q;
        renderCart();
        if (capped) { flash('Only ' + line.stock + ' available.'); }
    });

    document.getElementById('clearCart').addEventListener('click', function () { cart = []; renderCart(); });

    /* ---------- Payment, change, validation ---------- */
    function updateTotals() {
        var total = totalCents(), paid = cents(payment.value);
        var count = cart.reduce(function (s, l) { return s + l.qty; }, 0);
        document.getElementById('cartCount').textContent = count;
        document.getElementById('cartTotal').textContent = peso(total);
        document.getElementById('cartChange').textContent = peso(Math.max(paid - total, 0));

        var message = '';
        if (!cart.length) { message = 'Add at least one product to the sale.'; }
        else if (payment.value === '' || isNaN(parseFloat(payment.value))) { message = 'Enter the payment amount.'; }
        else if (paid < total) { message = 'Payment is less than the total (short by ' + peso(total - paid) + ').'; }
        else if (paid > 999999999) { message = 'Payment amount is too large.'; }

        // The browser blocks submitting while the message is set, so a bad sale never reaches the server from here.
        payment.setCustomValidity(message);
        completeBtn.disabled = message !== '';
        var shown = flashMsg || (message && cart.length && payment.value !== '' ? message : '');
        warn.textContent = shown;
        warn.classList.toggle('d-none', shown === '');
    }
    payment.addEventListener('input', updateTotals);

    document.getElementById('quickPay').addEventListener('click', function (e) {
        var b = e.target.closest('button'); if (!b) { return; }
        var base = cents(payment.value);
        if (b.hasAttribute('data-exact')) { base = totalCents(); }
        else { base += parseInt(b.dataset.add, 10) * 100; }
        payment.value = (base / 100).toFixed(2);
        updateTotals();
    });

    // Text for the confirmation dialog
    form.addEventListener('confirm:prepare', function () {
        var total = totalCents(), paid = cents(payment.value);
        var count = cart.reduce(function (s, l) { return s + l.qty; }, 0);
        form.dataset.confirmTitle = 'Complete this sale?';
        form.dataset.confirmText = count + ' item(s) \u00b7 Total ' + peso(total) + ' \u00b7 Payment ' + peso(paid) + ' \u00b7 Change ' + peso(paid - total) + '. Stock will be deducted.';
        form.dataset.confirmOk = 'Yes, complete sale';
        form.dataset.confirmVariant = 'berry';
        form.dataset.confirmIcon = 'fa-cash-register';
    });

    renderCart();
    loadProducts();
})();
</script>
@endpush
