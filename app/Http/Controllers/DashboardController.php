<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Used by both the admin and staff dashboard routes. */
    public function index(): View
    {
        $isAdmin = auth()->user()->isAdmin();

        return view('dashboard.index', [
            'isAdmin' => $isAdmin,
            'totalProducts' => Product::count(),
            'availableStock' => (int) Product::sum('available_stock'),
            'soldItems' => (int) SaleItem::sum('quantity'),
            'damagedItems' => (int) StockMovement::where('type', StockMovement::TYPE_DAMAGED)->sum('quantity'),
            'lowStockCount' => Product::lowStock()->count(),
            'lowStockProducts' => Product::with('category')->lowStock()->orderBy('available_stock')->limit(6)->get(),
            'recentSales' => Sale::with('items')->withSum('items as total_quantity', 'quantity')->latest()->limit(6)->get(),
            'recentProducts' => Product::with('category')->latest()->limit(6)->get(),
            'recentActivities' => $isAdmin ? ActivityLog::latest()->limit(8)->get() : collect(),
        ]);
    }

    /** JSON for the Chart.js sales overview. range = daily | weekly | monthly */
    public function salesChart(Request $request): JsonResponse
    {
        $range = $request->validate(['range' => ['required', 'in:daily,weekly,monthly']])['range'];
        $now = CarbonImmutable::now();

        if ($range === 'daily') {            // last 7 days
            $start = $now->subDays(6)->startOfDay();
            $periods = collect(range(0, 6))->map(fn ($i) => $start->addDays($i));
            $keyOf = fn ($d) => $d->format('Y-m-d');
            $labelOf = fn ($d) => $d->format('M d');
        } elseif ($range === 'weekly') {     // last 8 weeks
            $start = $now->startOfWeek()->subWeeks(7);
            $periods = collect(range(0, 7))->map(fn ($i) => $start->addWeeks($i));
            $keyOf = fn ($d) => $d->startOfWeek()->format('Y-m-d');
            $labelOf = fn ($d) => 'Wk of '.$d->format('M d');
        } else {                             // last 12 months
            $start = $now->startOfMonth()->subMonths(11);
            $periods = collect(range(0, 11))->map(fn ($i) => $start->addMonths($i));
            $keyOf = fn ($d) => $d->format('Y-m');
            $labelOf = fn ($d) => $d->format('M Y');
        }

        $totals = Sale::where('created_at', '>=', $start)
            ->get(['total_amount', 'created_at'])
            ->groupBy(fn ($sale) => $keyOf(CarbonImmutable::parse($sale->created_at)))
            ->map(fn ($group) => (float) $group->sum('total_amount'));

        $values = $periods->map(fn ($p) => round($totals[$keyOf($p)] ?? 0, 2));

        return response()->json([
            'labels' => $periods->map($labelOf)->values(),
            'values' => $values->values(),
            'total' => round($values->sum(), 2),
        ]);
    }
}
