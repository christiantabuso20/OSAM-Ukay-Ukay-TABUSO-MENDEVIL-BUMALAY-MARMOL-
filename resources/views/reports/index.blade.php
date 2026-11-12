@extends('layouts.app')
@section('title', 'Reports Generation')
@section('page-title', 'Reports Generation')

@push('styles')
<style>
    .report-tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:.75rem;margin-bottom:1.25rem}
    .report-tile{background:var(--vanilla);border:1px solid var(--beige);border-radius:12px;padding:.75rem 1rem}
    .report-tile .tile-label{font-size:.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.03em}
    .report-tile .tile-value{font-size:1.2rem;font-weight:600;color:var(--berry-dark);word-break:break-word}
    .report-head{border-bottom:2px solid var(--berry);padding-bottom:.75rem;margin-bottom:1rem}
    .report-head .brand{font-family:'Playfair Display',serif;color:var(--berry);font-size:1.3rem;font-weight:700}
    .report-head h3{font-size:1.1rem;color:var(--berry-dark);margin:.15rem 0 .35rem}
    .table-berry tfoot td{font-weight:600;background:var(--vanilla);border-top:2px solid var(--berry)}
    .export-group{display:flex;gap:.5rem;flex-wrap:wrap}
</style>
@endpush

@section('content')
    @php
        $isAdmin = auth()->user()->isAdmin();
        $grouped = collect($types)->groupBy('group', true);
        $selected = $types[$type];
        $previewLimit = 200;
        $totalRows = count($report['rows']);
    @endphp

    <div class="page-head">
        <div>
            <h2 class="section-heading">Reports Generation</h2>
        </div>
    </div>

    {{-- Report selector + filters --}}
    <form method="GET" action="{{ route('reports.index') }}" id="reportForm" class="card-box filter-box mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label small">Report</label>
                <select name="type" id="reportType" class="form-select form-select-sm">
                    @foreach ($grouped as $group => $items)
                        <optgroup label="{{ $group }}">
                            @foreach ($items as $key => $item)
                                <option value="{{ $key }}" @selected($type === $key)>{{ $item['label'] }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-2 col-md-6" data-filter="date">
                <label class="form-label small">Date <span class="text-muted">(week / month containing it)</span></label>
                <input type="date" name="date" value="{{ request('date', $today) }}" class="form-control form-control-sm">
            </div>

            <div class="col-lg-2 col-6" data-filter="range">
                <label class="form-label small">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
            </div>
            <div class="col-lg-2 col-6" data-filter="range">
                <label class="form-label small">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
            </div>

            <div class="col-lg-2 col-md-6" data-filter="category">
                <label class="form-label small">Category</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-2 col-md-6" data-filter="movement_type">
                <label class="form-label small">Movement Type</label>
                <select name="movement_type" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach ($movementTypes as $key => $label)
                        <option value="{{ $key }}" @selected(request('movement_type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-berry btn-sm px-3"><i class="fa-solid fa-magnifying-glass me-1"></i>Generate</button>
                <a href="{{ route('reports.index') }}" class="btn btn-light btn-sm px-3">Reset</a>
            </div>
        </div>
    </form>

    {{-- Report preview --}}
    <div class="card-box">
        <div class="card-box-header">
            <div class="report-head w-100 border-0 pb-0 mb-0">
                <div class="brand">OSAM Ukay-Ukay</div>
                <h3>{{ $report['title'] }}</h3>
                <div class="small"><strong>Coverage:</strong> {{ $report['coverage'] }}</div>
                <div class="small text-muted">Generated {{ $report['generated'] }} by {{ $report['generated_by'] }}</div>
            </div>
        </div>

        <div class="export-group mb-3">
            <button type="submit" form="reportForm" formaction="{{ route('reports.export', 'pdf') }}" formtarget="_blank" class="btn btn-berry btn-sm"><i class="fa-solid fa-file-pdf me-1"></i>Export PDF</button>
            <button type="submit" form="reportForm" formaction="{{ route('reports.export', 'excel') }}" class="btn btn-berry btn-sm"><i class="fa-solid fa-file-excel me-1"></i>Export Excel</button>
            <button type="submit" form="reportForm" formaction="{{ route('reports.export', 'print') }}" formtarget="_blank" class="btn btn-light btn-sm border"><i class="fa-solid fa-print me-1"></i>Print</button>
        </div>

        <div class="report-tiles">
            @foreach ($report['summary'] as $item)
                <div class="report-tile">
                    <div class="tile-label">{{ $item['label'] }}</div>
                    <div class="tile-value">{{ \App\Services\ReportBuilder::format($item['value'], $item['type']) }}</div>
                </div>
            @endforeach
        </div>

        @if ($totalRows === 0)
            <div class="empty-state py-5"><i class="fa-solid fa-file-lines"></i>No records found for this report and coverage.</div>
        @else
            <div class="table-responsive">
                <table class="table table-berry align-middle mb-0">
                    <thead>
                        <tr>
                            @foreach ($report['columns'] as $col)
                                <th class="{{ $col['type'] === 'text' ? '' : 'text-end' }}">{{ $col['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (array_slice($report['rows'], 0, $previewLimit) as $row)
                            <tr>
                                @foreach ($report['columns'] as $i => $col)
                                    <td class="{{ $col['type'] === 'text' ? '' : 'text-end text-nowrap' }}">{{ \App\Services\ReportBuilder::format($row[$i] ?? null, $col['type']) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                    @if (! empty($report['footer']) && $totalRows <= $previewLimit)
                        <tfoot>
                            <tr>
                                @foreach ($report['columns'] as $i => $col)
                                    <td class="{{ $col['type'] === 'text' ? '' : 'text-end text-nowrap' }}">{{ \App\Services\ReportBuilder::format($report['footer'][$i] ?? null, $col['type']) }}</td>
                                @endforeach
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            @if ($totalRows > $previewLimit)
                <p class="small text-muted mt-3 mb-0"><i class="fa-solid fa-circle-info me-1"></i>Preview shows the first {{ $previewLimit }} of {{ number_format($totalRows) }} rows. The PDF, Excel and print versions include every row.</p>
            @endif
        @endif
    </div>
@endsection

@push('scripts')
<script>
    // Show only the filters the selected report uses; hidden ones are disabled so they are not submitted.
    (function () {
        var filterMap = @json($filterMap);
        var select = document.getElementById('reportType');

        function apply() {
            var used = filterMap[select.value] || [];
            document.querySelectorAll('#reportForm [data-filter]').forEach(function (box) {
                var show = used.indexOf(box.getAttribute('data-filter')) !== -1;
                box.style.display = show ? '' : 'none';
                box.querySelectorAll('input, select').forEach(function (el) { el.disabled = !show; });
            });
        }

        select.addEventListener('change', function () {
            apply();
            document.getElementById('reportForm').submit();
        });
        apply();
    })();
</script>
@endpush
