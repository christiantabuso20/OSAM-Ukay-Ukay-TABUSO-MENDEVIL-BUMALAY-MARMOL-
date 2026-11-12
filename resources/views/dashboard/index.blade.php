@extends('layouts.app')
@section('title', $isAdmin ? 'Admin Dashboard' : 'Staff Dashboard')
@section('page-title', $isAdmin ? 'Admin Dashboard' : 'Staff Dashboard')

@section('content')
    <div class="dashboard-hero mb-4">
        <div>
            <div class="eyebrow">{{ $isAdmin ? 'ADMIN WORKSPACE' : 'STAFF WORKSPACE' }}</div>
            <h2 class="section-heading">Good to see you, {{ auth()->user()->name }}</h2>
            <p class="hero-copy">Keep today's stock, sales, and store activity moving with a quick look at what needs your attention.</p>
        </div>
        <div class="hero-date"><i class="fa-regular fa-calendar me-2"></i>{{ now()->format('l, M d, Y') }}</div>
    </div>

    <div class="quick-actions mb-4">
        <div><span class="eyebrow">QUICK ACTIONS</span><strong>What would you like to do?</strong></div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('sales.create') }}" class="quick-action"><i class="fa-solid fa-cash-register"></i><span>Record sale</span></a>
            <a href="{{ route('products.index') }}" class="quick-action"><i class="fa-solid fa-shirt"></i><span>View inventory</span></a>
            <a href="{{ route('reports.index') }}" class="quick-action"><i class="fa-solid fa-chart-column"></i><span>Open reports</span></a>
            @if ($isAdmin)<a href="{{ route('users.index') }}" class="quick-action"><i class="fa-solid fa-user-plus"></i><span>Manage users</span></a>@endif
        </div>
    </div>

    {{-- Statistic cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><x-stat-card icon="fa-shirt" label="Total Products" :value="number_format($totalProducts)" hint="Registered clothing items" /></div>
        <div class="col-6 col-xl-3"><x-stat-card icon="fa-boxes-stacked" label="Available Stocks" :value="number_format($availableStock)" hint="Pieces ready to sell" tone="rose" /></div>
        <div class="col-6 col-xl-3"><x-stat-card icon="fa-bag-shopping" label="Sold Items" :value="number_format($soldItems)" hint="Total pieces sold" tone="dark" /></div>
        <div class="col-6 col-xl-3"><x-stat-card icon="fa-triangle-exclamation" label="Low-Stock Alerts" :value="number_format($lowStockCount)" hint="At or below minimum level" tone="warn" /></div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Sales overview --}}
        <div class="col-lg-8">
            <div class="card-box h-100">
                <div class="card-box-header">
                    <h3>Sales Overview</h3>
                    <div class="btn-group btn-group-sm" id="rangeButtons">
                        <button type="button" class="btn btn-range active" data-range="daily">Daily</button>
                        <button type="button" class="btn btn-range" data-range="weekly">Weekly</button>
                        <button type="button" class="btn btn-range" data-range="monthly">Monthly</button>
                    </div>
                </div>
                <div class="text-muted small mb-2" id="chartCaption">Loading...</div>
                <div class="chart-wrap"><canvas id="salesChart"></canvas></div>
            </div>
        </div>

        {{-- Inventory summary --}}
        <div class="col-lg-4">
            <div class="card-box h-100">
                <div class="card-box-header"><h3>Inventory Summary</h3></div>
                <ul class="summary-list">
                    <li><span><i class="fa-solid fa-shirt"></i> Registered products</span><strong>{{ number_format($totalProducts) }}</strong></li>
                    <li><span><i class="fa-solid fa-boxes-stacked"></i> Available clothing items</span><strong>{{ number_format($availableStock) }}</strong></li>
                    <li><span><i class="fa-solid fa-bag-shopping"></i> Total sold quantity</span><strong>{{ number_format($soldItems) }}</strong></li>
                    <li><span><i class="fa-solid fa-heart-crack"></i> Total damaged items</span><strong>{{ number_format($damagedItems) }}</strong></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Low stock --}}
        <div class="col-lg-6">
            <div class="card-box h-100">
                <div class="card-box-header"><h3>Low-Stock Products</h3></div>
                @if ($lowStockProducts->isEmpty())
                    <div class="empty-state"><i class="fa-regular fa-circle-check"></i>No products are low on stock.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-berry align-middle mb-0">
                            <thead><tr><th>Product</th><th>Category</th><th class="text-end">Stock</th><th class="text-end">Min.</th></tr></thead>
                            <tbody>
                            @foreach ($lowStockProducts as $p)
                                <tr>
                                    <td>{{ $p->name }}<br><small class="text-muted">{{ $p->sku }}</small></td>
                                    <td>{{ $p->category?->name }}</td>
                                    <td class="text-end"><span class="pill {{ $p->available_stock <= 0 ? 'pill-danger' : 'pill-warn' }}">{{ $p->available_stock }}</span></td>
                                    <td class="text-end">{{ $p->minimum_stock }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Recent sales --}}
        <div class="col-lg-6">
            <div class="card-box h-100">
                <div class="card-box-header"><h3>Recent Sales</h3></div>
                @if ($recentSales->isEmpty())
                    <div class="empty-state"><i class="fa-solid fa-receipt"></i>No sales recorded yet.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-berry align-middle mb-0">
                            <thead><tr><th>Sale ID</th><th>Product(s)</th><th class="text-end">Qty</th><th class="text-end">Total</th><th>Date</th></tr></thead>
                            <tbody>
                            @foreach ($recentSales as $sale)
                                @php $first = $sale->items->first(); $more = $sale->items->count() - 1; @endphp
                                <tr>
                                    <td class="fw-medium">{{ $sale->sale_number }}</td>
                                    <td>{{ $first?->product_name }} @if ($more > 0)<small class="text-muted">+{{ $more }} more</small>@endif</td>
                                    <td class="text-end">{{ $sale->total_quantity }}</td>
                                    <td class="text-end">₱{{ number_format($sale->total_amount, 2) }}</td>
                                    <td class="text-nowrap">{{ $sale->created_at->format('M d, Y h:i A') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Recently added products --}}
        <div class="{{ $isAdmin ? 'col-lg-6' : 'col-12' }}">
            <div class="card-box h-100">
                <div class="card-box-header"><h3>Recently Added Products</h3></div>
                @if ($recentProducts->isEmpty())
                    <div class="empty-state"><i class="fa-solid fa-shirt"></i>No products added yet.</div>
                @else
                    <ul class="feed-list">
                        @foreach ($recentProducts as $p)
                            <li>
                                <div class="feed-icon"><i class="fa-solid fa-shirt"></i></div>
                                <div class="flex-grow-1">
                                    <div class="fw-medium">{{ $p->name }}</div>
                                    <small class="text-muted">{{ $p->category?->name }} &middot; {{ $p->size }} &middot; ₱{{ number_format($p->unit_price, 2) }}</small>
                                </div>
                                <small class="text-muted text-nowrap">{{ $p->created_at->diffForHumans() }}</small>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- Recent activities (admin only) --}}
        @if ($isAdmin)
            <div class="col-lg-6">
                <div class="card-box h-100">
                    <div class="card-box-header"><h3>Recent Activities</h3></div>
                    @if ($recentActivities->isEmpty())
                        <div class="empty-state"><i class="fa-solid fa-clock-rotate-left"></i>No activities recorded yet.</div>
                    @else
                        <ul class="feed-list">
                            @foreach ($recentActivities as $log)
                                <li>
                                    <div class="feed-icon"><i class="fa-solid fa-user"></i></div>
                                    <div class="flex-grow-1">
                                        <div><span class="fw-medium">{{ $log->user_name }}</span> <small class="text-muted">&middot; {{ $log->module }}</small></div>
                                        <small class="text-muted">{{ $log->description }}</small>
                                    </div>
                                    <small class="text-muted text-nowrap">{{ $log->created_at->diffForHumans() }}</small>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    var url = @json(route('dashboard.sales-chart'));
    var captions = { daily: 'Last 7 days', weekly: 'Last 8 weeks', monthly: 'Last 12 months' };
    var chart = null;

    function load(range) {
        fetch(url + '?range=' + range, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                document.getElementById('chartCaption').textContent =
                    captions[range] + ' - total sales: ₱' + Number(d.total).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                if (chart) { chart.destroy(); }
                chart = new Chart(document.getElementById('salesChart'), {
                    type: range === 'daily' ? 'bar' : 'line',
                    data: {
                        labels: d.labels,
                        datasets: [{
                            label: 'Sales (₱)', data: d.values,
                            backgroundColor: 'rgba(113,59,80,0.85)', borderColor: '#713B50',
                            borderWidth: 2, borderRadius: 6, tension: 0.3, fill: false, pointBackgroundColor: '#713B50'
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, grid: { color: '#E8DCCB' } }, x: { grid: { display: false } } }
                    }
                });
            })
            .catch(function () { document.getElementById('chartCaption').textContent = 'Unable to load chart data.'; });
    }

    document.querySelectorAll('#rangeButtons .btn-range').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('#rangeButtons .btn-range').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            load(btn.dataset.range);
        });
    });
    load('daily');
})();
</script>
@endpush
