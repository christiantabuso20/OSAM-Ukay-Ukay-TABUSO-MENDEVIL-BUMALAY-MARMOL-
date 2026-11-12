@php
    $role = auth()->user()->role;

    $menu = [
        ['group' => 'Workspace', 'items' => [
            ['label' => 'Dashboard', 'icon' => 'fa-gauge-high', 'route' => auth()->user()->dashboardRoute(), 'active' => ['admin.dashboard', 'staff.dashboard'], 'roles' => ['admin', 'staff']],
        ]],
        ['group' => 'Operations', 'items' => [
            ['label' => 'Clothing Inventory', 'icon' => 'fa-shirt', 'route' => 'products.index', 'active' => ['products.*'], 'roles' => ['admin', 'staff']],
            ['label' => 'Stock Monitoring', 'icon' => 'fa-boxes-stacked', 'route' => 'stock.index', 'active' => ['stock.*'], 'roles' => ['admin', 'staff']],
            ['label' => 'Sales Recording', 'icon' => 'fa-cash-register', 'route' => 'sales.index', 'active' => ['sales.*'], 'roles' => ['admin', 'staff']],
        ]],
        ['group' => 'Insights', 'items' => [
            ['label' => 'Reports Generation', 'icon' => 'fa-chart-column', 'route' => 'reports.index', 'active' => ['reports.*'], 'roles' => ['admin', 'staff']],
        ]],
        ['group' => 'Administration', 'items' => [
            ['label' => 'Category Management', 'icon' => 'fa-tags', 'route' => 'categories.index', 'active' => ['categories.*'], 'roles' => ['admin']],
            ['label' => 'User Management', 'icon' => 'fa-users-gear', 'route' => 'users.index', 'active' => ['users.*'], 'roles' => ['admin']],
            ['label' => 'Activity Logs', 'icon' => 'fa-clock-rotate-left', 'route' => 'activity-logs.index', 'active' => ['activity-logs.*'], 'roles' => ['admin']],
        ]],
    ];
@endphp

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <span class="sidebar-logo"><i class="fa-solid fa-shirt"></i></span>
        <span class="sidebar-brand-text"><strong>OSAM</strong><small>Ukay-Ukay portal</small></span>
    </div>

    <div class="sidebar-collapse-wrap">
        <button type="button" class="sidebar-collapse-btn" id="sidebarCollapse" title="Collapse / expand sidebar" aria-label="Collapse or expand sidebar">
            <i class="fa-solid fa-angles-left icon-collapse"></i>
            <i class="fa-solid fa-angles-right icon-expand"></i>
            <span class="sidebar-label">Collapse</span>
        </button>
    </div>

    <nav class="sidebar-nav">
        @foreach ($menu as $section)
            @php $visibleItems = collect($section['items'])->filter(fn ($item) => in_array($role, $item['roles'], true)); @endphp
            @if ($visibleItems->isNotEmpty())
                <div class="sidebar-section-label sidebar-label">{{ $section['group'] }}</div>
                @foreach ($visibleItems as $item)
                    <a href="{{ route($item['route']) }}" class="sidebar-link {{ request()->routeIs(...($item['active'] ?? [$item['route']])) ? 'active' : '' }}" title="{{ $item['label'] }}">
                        <i class="fa-solid {{ $item['icon'] }}"></i>
                        <span class="sidebar-label">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            @endif
        @endforeach
    </nav>

    <form method="POST" action="{{ route('logout') }}" class="sidebar-footer" data-confirm-title="Log out?" data-confirm-text="You will need to sign in again to continue using OSAM Ukay-Ukay." data-confirm-ok="Yes, log out" data-confirm-variant="berry" data-confirm-icon="fa-right-from-bracket">
        @csrf
        <button type="submit" class="sidebar-link logout-link" title="Logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span class="sidebar-label">Logout</span>
        </button>
    </form>
</aside>
