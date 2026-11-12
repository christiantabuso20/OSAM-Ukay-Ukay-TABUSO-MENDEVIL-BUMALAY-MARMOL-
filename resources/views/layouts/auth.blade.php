<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Login') | OSAM Ukay-Ukay</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/auth.css') }}" rel="stylesheet">
</head>
<body class="auth-body">
<div class="auth-wrap">

    {{-- LEFT: branding (desktop / laptop / tablet landscape) --}}
    <section class="auth-brand d-none d-lg-block">
        {{-- Curved background with a soft rose band along the edge --}}
        <svg class="auth-brand-bg" viewBox="0 0 1000 1000" preserveAspectRatio="none" aria-hidden="true">
            <defs>
                <linearGradient id="berryBg" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#713B50"/>
                    <stop offset="1" stop-color="#482735"/>
                </linearGradient>
            </defs>
            <path d="M0 0 H900 C985 140 800 310 850 490 C895 650 975 790 920 1000 H0 Z" fill="#B98596" opacity=".55"/>
            <path d="M0 0 H868 C948 140 765 310 815 490 C860 650 940 790 885 1000 H0 Z" fill="url(#berryBg)"/>
            <circle cx="120" cy="60" r="190" fill="#B98596" opacity=".10"/>
            <circle cx="700" cy="760" r="260" fill="#B98596" opacity=".08"/>
        </svg>

        <i class="fa-solid fa-shirt brand-bg-shirt" aria-hidden="true"></i>
        <i class="fa-solid fa-bag-shopping brand-bg-bag" aria-hidden="true"></i>

        <div class="auth-brand-inner">
            <div class="brand-lockup">
                <div class="brand-logo"><i class="fa-solid fa-shirt"></i></div>
                <span class="brand-divider"></span>
                <div>
                    <div class="brand-lockup-name">OSAM Ukay-Ukay</div>
                    <div class="brand-lockup-tag">Smart Inventory. Better Business.</div>
                </div>
            </div>

            <div class="brand-hero">
                <p class="brand-welcome-small">Welcome to</p>
                <h1 class="brand-name">OSAM <span>Ukay-Ukay</span></h1>
                <svg class="brand-swoosh" viewBox="0 0 260 14" aria-hidden="true"><path d="M3 9 C60 2 150 2 257 8" fill="none" stroke="#B98596" stroke-width="4" stroke-linecap="round"/></svg>
                <p class="brand-tagline">Clothing Inventory Monitoring and Reporting System</p>
                <p class="brand-text">Manage your clothing inventory, monitor stocks, and keep track of your business operations in one place.</p>
            </div>

            <div class="brand-features">
                <div class="brand-feature"><span><i class="fa-solid fa-box-open"></i></span>Track<br>Inventory</div>
                <i class="feature-sep"></i>
                <div class="brand-feature"><span><i class="fa-solid fa-chart-column"></i></span>Generate<br>Reports</div>
                <i class="feature-sep"></i>
                <div class="brand-feature"><span><i class="fa-solid fa-shirt"></i></span>Manage<br>Products</div>
            </div>
        </div>

        {{-- Clothes rack --}}
        @php
            $rackColors = ['#482735', '#713B50', '#B98596', '#F5F0E8', '#E8DCCB', '#5C3045', '#9C6277', '#F5F0E8'];
        @endphp
        <svg class="brand-rack" viewBox="0 0 760 260" aria-hidden="true">
            <defs>
                <symbol id="shirtShape" viewBox="0 0 100 100">
                    <path d="M34 6 L8 20 L16 42 L28 36 L28 96 L72 96 L72 36 L84 42 L92 20 L66 6 Q50 22 34 6 Z" stroke="rgba(0,0,0,.18)" stroke-width="1.2" stroke-linejoin="round"/>
                </symbol>
            </defs>
            <g transform="rotate(7 0 0) translate(-10 26)">
                <line x1="-40" y1="14" x2="720" y2="14" stroke="#B98596" stroke-width="5" stroke-linecap="round" opacity=".85"/>
                @foreach ($rackColors as $i => $color)
                    @php $x = 20 + $i * 74; @endphp
                    <path d="M{{ $x + 45 }} 14 v-1 q0 -9 -8 -9" fill="none" stroke="#C99A78" stroke-width="3" stroke-linecap="round"/>
                    <path d="M{{ $x + 20 }} 40 L{{ $x + 45 }} 20 L{{ $x + 70 }} 40 Z" fill="none" stroke="#C99A78" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <use href="#shirtShape" x="{{ $x }}" y="20" width="90" height="170" fill="{{ $color }}"/>
                @endforeach
            </g>
        </svg>
    </section>

    {{-- RIGHT: form --}}
    <section class="auth-form-side">
        <span class="blob blob-1"></span>
        <span class="blob blob-2"></span>

        {{-- Compact brand for tablet / mobile --}}
        <div class="auth-mobile-brand d-lg-none">
            <div class="brand-logo brand-logo-sm"><i class="fa-solid fa-shirt"></i></div>
            <div>
                <div class="mobile-brand-name">OSAM Ukay-Ukay</div>
                <div class="mobile-brand-tag">Smart Inventory. Better Business.</div>
            </div>
        </div>

        <div class="auth-card">
            <div class="card-top">
                <span class="card-line"></span>
                <span class="card-badge"><span><i class="fa-solid @yield('card-icon', 'fa-lock')"></i></span></span>
                <span class="card-line"></span>
            </div>
            @yield('content')
            <div class="auth-or"><span>or</span></div>
            <div class="auth-secure"><i class="fa-solid fa-shield-halved"></i>Secure Inventory Management Access</div>
        </div>
    </section>
</div>
<script src="{{ asset('js/auth.js') }}"></script>
</body>
</html>
