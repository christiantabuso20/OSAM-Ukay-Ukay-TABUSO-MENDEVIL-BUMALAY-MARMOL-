<header class="topbar">
    <div class="d-flex align-items-center gap-3">
        <button type="button" class="icon-btn" id="sidebarToggle" aria-label="Toggle sidebar"><i class="fa-solid fa-bars"></i></button>
        <div>
            <div class="portal-kicker">OSAM OPERATIONS PORTAL</div>
            <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2 gap-md-3">
        @hasSection('search')
            @yield('search')
        @endif

        {{-- Low-stock notifications --}}
        <div class="dropdown">
            <button class="icon-btn position-relative" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                <i class="fa-regular fa-bell"></i>
                @if ($navLowStockCount > 0)
                    <span class="notif-dot">{{ $navLowStockCount > 99 ? '99+' : $navLowStockCount }}</span>
                @endif
            </button>
            <div class="dropdown-menu dropdown-menu-end notif-menu">
                <div class="dropdown-header">Low-stock alerts</div>
                @forelse ($navLowStockItems as $item)
                    <div class="notif-item">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <div>
                            <div class="fw-medium">{{ $item->name }}</div>
                            <small class="text-muted">{{ $item->available_stock }} left (minimum {{ $item->minimum_stock }})</small>
                        </div>
                    </div>
                @empty
                    <div class="notif-empty">No low-stock products.</div>
                @endforelse
                @if ($navLowStockCount > $navLowStockItems->count())
                    <div class="notif-more">+ {{ $navLowStockCount - $navLowStockItems->count() }} more</div>
                @endif
            </div>
        </div>

        {{-- Profile --}}
        <div class="dropdown">
            <button class="profile-btn" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="d-none d-md-block text-start lh-sm">
                    <span class="d-block fw-medium">{{ auth()->user()->name }}</span>
                    <small class="text-muted text-capitalize">{{ auth()->user()->role }}</small>
                </span>
                <i class="fa-solid fa-chevron-down small d-none d-md-block"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li class="px-3 py-2">
                    <div class="fw-medium">{{ auth()->user()->name }}</div>
                    <small class="text-muted">{{ auth()->user()->email }}</small><br>
                    <span class="badge badge-berry text-capitalize mt-1">{{ auth()->user()->role }}</span>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}" data-confirm-title="Log out?" data-confirm-text="You will need to sign in again to continue using OSAM Ukay-Ukay." data-confirm-ok="Yes, log out" data-confirm-variant="berry" data-confirm-icon="fa-right-from-bracket">
                        @csrf
                        <button type="submit" class="dropdown-item"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
