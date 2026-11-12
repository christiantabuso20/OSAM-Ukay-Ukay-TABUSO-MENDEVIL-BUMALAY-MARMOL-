<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Builds every report as one plain array, so the same data feeds the
 * on-screen preview, the print view, the PDF and the Excel file.
 *
 * Report array shape:
 *   type, title, coverage, generated, generated_by, filename,
 *   summary : [ ['label' => '', 'value' => x, 'type' => 'text|int|money'], ... ]
 *   columns : [ ['label' => '', 'type' => 'text|int|money'], ... ]
 *   rows    : [ [cell, cell, ...], ... ]   (same order as columns)
 *   footer  : null or one row of totals (same order as columns)
 */
class ReportBuilder
{
    /** filters: which inputs the report uses (category, date, range, movement_type). */
    public const TYPES = [
        'inventory_complete' => ['group' => 'Inventory Reports', 'label' => 'Complete Inventory Report', 'roles' => ['admin', 'staff'], 'filters' => ['category']],
        'inventory_available' => ['group' => 'Inventory Reports', 'label' => 'Available Stock Report', 'roles' => ['admin', 'staff'], 'filters' => ['category']],
        'inventory_low' => ['group' => 'Inventory Reports', 'label' => 'Low-Stock Report', 'roles' => ['admin', 'staff'], 'filters' => ['category']],
        'inventory_damaged' => ['group' => 'Inventory Reports', 'label' => 'Damaged Items Report', 'roles' => ['admin'], 'filters' => ['range']],
        'inventory_movements' => ['group' => 'Inventory Reports', 'label' => 'Stock Movement Report', 'roles' => ['admin'], 'filters' => ['range', 'movement_type']],
        'sales_daily' => ['group' => 'Sales Reports', 'label' => 'Daily Sales Report', 'roles' => ['admin', 'staff'], 'filters' => ['date']],
        'sales_weekly' => ['group' => 'Sales Reports', 'label' => 'Weekly Sales Report', 'roles' => ['admin', 'staff'], 'filters' => ['date']],
        'sales_monthly' => ['group' => 'Sales Reports', 'label' => 'Monthly Sales Report', 'roles' => ['admin', 'staff'], 'filters' => ['date']],
        'sales_custom' => ['group' => 'Sales Reports', 'label' => 'Custom Date Range Sales Report', 'roles' => ['admin', 'staff'], 'filters' => ['range']],
        'sales_best' => ['group' => 'Sales Reports', 'label' => 'Best-Selling Products Report', 'roles' => ['admin', 'staff'], 'filters' => ['range']],
    ];

    private User $user;

    /** Report types the given user is allowed to open. */
    public static function typesFor(User $user): array
    {
        return array_filter(self::TYPES, fn (array $t) => in_array($user->role, $t['roles'], true));
    }

    /** Format a cell for display (screen, print and PDF). */
    public static function format(mixed $value, string $type): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return match ($type) {
            'money' => '₱'.number_format((float) $value, 2),
            'int' => number_format((int) $value),
            default => (string) $value,
        };
    }

    /**
     * @param  array{category?:?int,date?:CarbonImmutable,from?:?CarbonImmutable,to?:?CarbonImmutable,movement_type?:?string}  $filters
     */
    public function build(string $type, array $filters, User $user): array
    {
        $this->user = $user;

        $report = match ($type) {
            'inventory_complete' => $this->inventory($filters, 'complete'),
            'inventory_available' => $this->inventory($filters, 'available'),
            'inventory_low' => $this->inventory($filters, 'low'),
            'inventory_damaged' => $this->damaged($filters),
            'inventory_movements' => $this->movements($filters),
            'sales_daily' => $this->salesPeriod($filters, 'daily'),
            'sales_weekly' => $this->salesPeriod($filters, 'weekly'),
            'sales_monthly' => $this->salesPeriod($filters, 'monthly'),
            'sales_custom' => $this->salesCustom($filters),
            'sales_best' => $this->bestSelling($filters),
        };

        $report['type'] = $type;
        $report['generated'] = now()->format('F d, Y h:i A');
        $report['generated_by'] = $user->name;
        $report['filename'] = Str::slug($report['title']).'-'.now()->format('Ymd-His');
        $report['footer'] ??= null;

        return $report;
    }

    // ------------------------------------------------------------------
    // Inventory reports
    // ------------------------------------------------------------------

    private function inventory(array $f, string $kind): array
    {
        $categoryId = $f['category'] ?? null;

        $products = Product::with('category')
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($kind === 'available', fn ($q) => $q->where('available_stock', '>', 0))
            ->when($kind === 'low', fn ($q) => $q->lowStock())
            ->when($kind === 'low', fn ($q) => $q->orderBy('available_stock'))
            ->orderBy('name')
            ->get();

        $units = (int) $products->sum('available_stock');
        $value = round((float) $products->sum(fn ($p) => (float) $p->unit_price * $p->available_stock), 2);
        $lowCount = $products->filter(fn ($p) => $p->stock_status !== 'in_stock')->count();
        $outCount = $products->filter(fn ($p) => $p->stock_status === 'out_of_stock')->count();

        $coverage = 'Current stock as of '.now()->format('M d, Y h:i A');
        if ($categoryId && ($name = $products->first()?->category?->name ?? \App\Models\Category::find($categoryId)?->name)) {
            $coverage .= ' | Category: '.$name;
        }

        if ($kind === 'complete') {
            return [
                'title' => 'Complete Inventory Report',
                'coverage' => $coverage,
                'summary' => [
                    $this->s('Total Products', $products->count(), 'int'),
                    $this->s('Units Available', $units, 'int'),
                    $this->s('Inventory Value', $value, 'money'),
                    $this->s('Low / Out-of-Stock Products', $lowCount, 'int'),
                ],
                'columns' => [
                    $this->c('SKU'), $this->c('Product'), $this->c('Category'), $this->c('Clothing Type'), $this->c('Size'),
                    $this->c('Condition'), $this->c('Unit Price', 'money'), $this->c('Available Stock', 'int'),
                    $this->c('Min. Level', 'int'), $this->c('Status'),
                ],
                'rows' => $products->map(fn ($p) => [
                    $p->sku, $p->name, $p->category?->name ?? '-', $p->clothing_type ?: '-', $p->size,
                    $p->condition, (float) $p->unit_price, $p->available_stock, $p->minimum_stock, $p->stock_status_label,
                ])->all(),
                'footer' => ['TOTAL', '', '', '', '', '', null, $units, null, ''],
            ];
        }

        if ($kind === 'available') {
            return [
                'title' => 'Available Stock Report',
                'coverage' => $coverage,
                'summary' => [
                    $this->s('Products In Stock', $products->count(), 'int'),
                    $this->s('Units Available', $units, 'int'),
                    $this->s('Stock Value', $value, 'money'),
                ],
                'columns' => [
                    $this->c('SKU'), $this->c('Product'), $this->c('Category'), $this->c('Size'), $this->c('Condition'),
                    $this->c('Unit Price', 'money'), $this->c('Available Stock', 'int'), $this->c('Stock Value', 'money'),
                ],
                'rows' => $products->map(fn ($p) => [
                    $p->sku, $p->name, $p->category?->name ?? '-', $p->size, $p->condition,
                    (float) $p->unit_price, $p->available_stock, round((float) $p->unit_price * $p->available_stock, 2),
                ])->all(),
                'footer' => ['TOTAL', '', '', '', '', null, $units, $value],
            ];
        }

        return [
            'title' => 'Low-Stock Report',
            'coverage' => $coverage.' | Products at or below their minimum stock level',
            'summary' => [
                $this->s('Low-Stock Products', $products->count(), 'int'),
                $this->s('Out of Stock', $outCount, 'int'),
                $this->s('Units Remaining', $units, 'int'),
            ],
            'columns' => [
                $this->c('SKU'), $this->c('Product'), $this->c('Category'), $this->c('Size'),
                $this->c('Available Stock', 'int'), $this->c('Min. Level', 'int'), $this->c('Status'),
            ],
            'rows' => $products->map(fn ($p) => [
                $p->sku, $p->name, $p->category?->name ?? '-', $p->size,
                $p->available_stock, $p->minimum_stock, $p->stock_status_label,
            ])->all(),
            'footer' => null,
        ];
    }

    private function damaged(array $f): array
    {
        [$from, $to] = $this->range($f);

        $movements = $this->movementQuery($from, $to)
            ->where('type', StockMovement::TYPE_DAMAGED)
            ->orderBy('created_at')->orderBy('id')
            ->get();

        $units = (int) $movements->sum('quantity');
        $lost = round((float) $movements->sum(fn ($m) => $m->product ? (float) $m->product->unit_price * $m->quantity : 0), 2);

        return [
            'title' => 'Damaged Items Report',
            'coverage' => $this->rangeText($from, $to),
            'summary' => [
                $this->s('Damaged Records', $movements->count(), 'int'),
                $this->s('Damaged Units', $units, 'int'),
                $this->s('Est. Value Lost (current prices)', $lost, 'money'),
            ],
            'columns' => [
                $this->c('Movement ID'), $this->c('Date & Time'), $this->c('Product'), $this->c('SKU'),
                $this->c('Quantity', 'int'), $this->c('Previous Stock', 'int'), $this->c('Updated Stock', 'int'),
                $this->c('Remarks'), $this->c('Recorded By'),
            ],
            'rows' => $movements->map(fn ($m) => [
                $m->code, $m->created_at->format('M d, Y h:i A'), $m->product?->name ?? 'Deleted product', $m->product?->sku ?? '-',
                $m->quantity, $m->previous_stock, $m->updated_stock, $m->remarks ?: '-', $m->user?->name ?? 'Unknown',
            ])->all(),
            'footer' => ['TOTAL', '', '', '', $units, null, null, '', ''],
        ];
    }

    private function movements(array $f): array
    {
        [$from, $to] = $this->range($f);
        $type = $f['movement_type'] ?? null;

        $movements = $this->movementQuery($from, $to)
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderBy('created_at')->orderBy('id')
            ->get();

        $in = (int) $movements->where('type', StockMovement::TYPE_IN)->sum('quantity');
        $out = (int) $movements->where('type', StockMovement::TYPE_OUT)->sum('quantity');
        $damaged = (int) $movements->where('type', StockMovement::TYPE_DAMAGED)->sum('quantity');

        $coverage = $this->rangeText($from, $to);
        if ($type) {
            $coverage .= ' | Type: '.StockMovement::TYPES[$type];
        }

        return [
            'title' => 'Stock Movement Report',
            'coverage' => $coverage,
            'summary' => [
                $this->s('Movements', $movements->count(), 'int'),
                $this->s('Total Stock In', $in, 'int'),
                $this->s('Total Stock Out', $out, 'int'),
                $this->s('Total Damaged', $damaged, 'int'),
            ],
            'columns' => [
                $this->c('Movement ID'), $this->c('Date & Time'), $this->c('Product'), $this->c('SKU'), $this->c('Type'),
                $this->c('Quantity', 'int'), $this->c('Previous Stock', 'int'), $this->c('Updated Stock', 'int'),
                $this->c('Remarks'), $this->c('User'),
            ],
            'rows' => $movements->map(fn ($m) => [
                $m->code, $m->created_at->format('M d, Y h:i A'), $m->product?->name ?? 'Deleted product', $m->product?->sku ?? '-',
                $m->type_label, $m->quantity, $m->previous_stock, $m->updated_stock, $m->remarks ?: '-', $m->user?->name ?? 'Unknown',
            ])->all(),
            'footer' => null,
        ];
    }

    private function movementQuery(?CarbonImmutable $from, ?CarbonImmutable $to)
    {
        return StockMovement::with(['product', 'user'])
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to->endOfDay()));
    }

    // ------------------------------------------------------------------
    // Sales reports (staff only ever see the sales they recorded)
    // ------------------------------------------------------------------

    private function salesPeriod(array $f, string $kind): array
    {
        $date = $f['date'] ?? CarbonImmutable::today();

        [$from, $to, $coverage, $title] = match ($kind) {
            'daily' => [$date->startOfDay(), $date->endOfDay(), $date->format('l, F d, Y'), 'Daily Sales Report'],
            'weekly' => [$date->startOfWeek(), $date->endOfWeek(), $date->startOfWeek()->format('M d, Y').' to '.$date->endOfWeek()->format('M d, Y'), 'Weekly Sales Report'],
            'monthly' => [$date->startOfMonth(), $date->endOfMonth(), $date->format('F Y'), 'Monthly Sales Report'],
        };

        return $this->salesReport($title, $coverage, $from, $to);
    }

    private function salesCustom(array $f): array
    {
        [$from, $to] = $this->range($f);

        return $this->salesReport('Custom Date Range Sales Report', $this->rangeText($from, $to), $from, $to);
    }

    private function salesReport(string $title, string $coverage, ?CarbonImmutable $from, ?CarbonImmutable $to): array
    {
        $sales = Sale::query()
            ->when(! $this->user->isAdmin(), fn ($q) => $q->where('user_id', $this->user->id))
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to->endOfDay()))
            ->withSum('items as total_quantity', 'quantity')
            ->orderBy('created_at')->orderBy('id')
            ->get();

        $count = $sales->count();
        $items = (int) $sales->sum('total_quantity');
        $total = round((float) $sales->sum('total_amount'), 2);
        $payments = round((float) $sales->sum('payment_amount'), 2);
        $change = round((float) $sales->sum('change_amount'), 2);

        if (! $this->user->isAdmin()) {
            $coverage .= ' | Your sales only';
        }

        return [
            'title' => $title,
            'coverage' => $coverage,
            'summary' => [
                $this->s('Transactions', $count, 'int'),
                $this->s('Items Sold', $items, 'int'),
                $this->s('Total Sales', $total, 'money'),
                $this->s('Average Sale', $count > 0 ? round($total / $count, 2) : 0, 'money'),
            ],
            'columns' => [
                $this->c('Sales ID'), $this->c('Date & Time'), $this->c('Items Sold', 'int'), $this->c('Total Amount', 'money'),
                $this->c('Payment', 'money'), $this->c('Change', 'money'), $this->c('Staff'),
            ],
            'rows' => $sales->map(fn ($s) => [
                $s->sale_number, $s->created_at->format('M d, Y h:i A'), (int) $s->total_quantity,
                (float) $s->total_amount, (float) $s->payment_amount, (float) $s->change_amount, $s->staff_name,
            ])->all(),
            'footer' => ['TOTAL', '', $items, $total, $payments, $change, ''],
        ];
    }

    private function bestSelling(array $f): array
    {
        [$from, $to] = $this->range($f);

        $all = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->when(! $this->user->isAdmin(), fn ($q) => $q->where('sales.user_id', $this->user->id))
            ->when($from, fn ($q) => $q->where('sales.created_at', '>=', $from->startOfDay()))
            ->when($to, fn ($q) => $q->where('sales.created_at', '<=', $to->endOfDay()))
            ->selectRaw('sale_items.sku as sku, MAX(sale_items.product_name) as product_name, SUM(sale_items.quantity) as qty, SUM(sale_items.subtotal) as revenue, COUNT(DISTINCT sale_items.sale_id) as transactions')
            ->groupBy('sale_items.sku')
            ->orderByDesc('qty')
            ->orderByDesc('revenue')
            ->get();

        $top = $all->take(20)->values();
        $coverage = $this->rangeText($from, $to).(! $this->user->isAdmin() ? ' | Your sales only' : '');
        $topUnits = (int) $top->sum('qty');
        $topRevenue = round((float) $top->sum('revenue'), 2);

        return [
            'title' => 'Best-Selling Products Report',
            'coverage' => $coverage.' | Top 20 by quantity sold',
            'summary' => [
                $this->s('Different Products Sold', $all->count(), 'int'),
                $this->s('Units Sold (all products)', (int) $all->sum('qty'), 'int'),
                $this->s('Revenue (all products)', round((float) $all->sum('revenue'), 2), 'money'),
                $this->s('Top Seller', $top->first()->product_name ?? '-', 'text'),
            ],
            'columns' => [
                $this->c('Rank', 'int'), $this->c('SKU'), $this->c('Product'),
                $this->c('Quantity Sold', 'int'), $this->c('Sales Amount', 'money'), $this->c('Transactions', 'int'),
            ],
            'rows' => $top->map(fn ($r, $i) => [
                $i + 1, $r->sku, $r->product_name, (int) $r->qty, round((float) $r->revenue, 2), (int) $r->transactions,
            ])->all(),
            'footer' => ['', '', 'TOTAL (listed)', $topUnits, $topRevenue, null],
        ];
    }

    // ------------------------------------------------------------------
    // Small helpers
    // ------------------------------------------------------------------

    private function s(string $label, mixed $value, string $type): array
    {
        return ['label' => $label, 'value' => $value, 'type' => $type];
    }

    private function c(string $label, string $type = 'text'): array
    {
        return ['label' => $label, 'type' => $type];
    }

    /** @return array{0:?CarbonImmutable,1:?CarbonImmutable} from/to, swapped if entered backwards. */
    private function range(array $f): array
    {
        $from = $f['from'] ?? null;
        $to = $f['to'] ?? null;

        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    private function rangeText(?CarbonImmutable $from, ?CarbonImmutable $to): string
    {
        return match (true) {
            $from !== null && $to !== null => $from->format('M d, Y').' to '.$to->format('M d, Y'),
            $from !== null => 'From '.$from->format('M d, Y'),
            $to !== null => 'Up to '.$to->format('M d, Y'),
            default => 'All dates',
        };
    }
}
