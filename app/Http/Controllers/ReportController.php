<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\StockMovement;
use App\Services\ReportBuilder;
use App\Support\SimplePdf;
use App\Support\SimpleXlsx;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    /** Report selector + on-screen preview. */
    public function index(Request $request, ReportBuilder $builder): View
    {
        $user = $request->user();
        $types = ReportBuilder::typesFor($user);
        $type = $this->resolveType($request, $types);

        return view('reports.index', [
            'types' => $types,
            'type' => $type,
            'report' => $builder->build($type, $this->filters($request), $user),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'movementTypes' => StockMovement::TYPES,
            'filterMap' => collect($types)->map(fn ($t) => $t['filters'])->all(),
            'today' => CarbonImmutable::today()->format('Y-m-d'),
        ]);
    }

    /** Download (PDF, Excel) or open the print view. */
    public function export(Request $request, ReportBuilder $builder, string $format): Response|BinaryFileResponse|View|RedirectResponse
    {
        $user = $request->user();
        $types = ReportBuilder::typesFor($user);

        if (! $request->filled('type')) {
            return redirect()->route('reports.index');
        }

        $type = $this->resolveType($request, $types);
        $report = $builder->build($type, $this->filters($request), $user);

        if ($format === 'excel') {
            ActivityLog::record('Reports', 'export_excel', "Exported {$report['title']} to Excel");

            return response()->download(
                SimpleXlsx::build($report),
                $report['filename'].'.xlsx',
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )->deleteFileAfterSend(true);
        }

        if ($format === 'pdf') {
            ActivityLog::record('Reports', 'export_pdf', "Exported {$report['title']} to PDF");

            return response(SimplePdf::build($report), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$report['filename'].'.pdf"',
            ]);
        }

        ActivityLog::record('Reports', 'print', "Printed {$report['title']}");

        return view('reports.print', ['report' => $report, 'autoPrint' => true, 'pdfFallback' => false]);
    }

    // ------------------------------------------------------------------

    /** Unknown type -> first allowed report. A real report the role may not open -> 403. */
    private function resolveType(Request $request, array $allowedTypes): string
    {
        $type = $request->query('type');

        if (is_string($type) && isset(ReportBuilder::TYPES[$type])) {
            abort_unless(isset($allowedTypes[$type]), 403, 'You do not have permission to open this report.');

            return $type;
        }

        return array_key_first($allowedTypes);
    }

    private function filters(Request $request): array
    {
        $category = $request->query('category');
        $movementType = $request->query('movement_type');

        return [
            'category' => is_numeric($category) ? (int) $category : null,
            'date' => $this->parseDate($request->query('date')) ?? CarbonImmutable::today(),
            'from' => $this->parseDate($request->query('date_from')),
            'to' => $this->parseDate($request->query('date_to')),
            'movement_type' => is_string($movementType) && isset(StockMovement::TYPES[$movementType]) ? $movementType : null,
        ];
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
