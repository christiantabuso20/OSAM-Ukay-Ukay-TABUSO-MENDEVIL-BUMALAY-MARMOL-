{{-- Shared report body used by the print view and the PDF. Styles come from the wrapper page. --}}
<div class="doc-head">
    <div class="brand">OSAM Ukay-Ukay</div>
    <div class="system">Clothing Inventory Monitoring and Reporting System</div>
    <h1>{{ $report['title'] }}</h1>
    <div class="meta"><strong>Coverage:</strong> {{ $report['coverage'] }}</div>
    <div class="meta"><strong>Generated:</strong> {{ $report['generated'] }} by {{ $report['generated_by'] }}</div>
</div>

<table class="summary">
    @foreach ($report['summary'] as $item)
        <tr>
            <td class="lbl">{{ $item['label'] }}</td>
            <td class="val">{{ \App\Services\ReportBuilder::format($item['value'], $item['type']) }}</td>
        </tr>
    @endforeach
</table>

<table class="data">
    <thead>
        <tr>
            @foreach ($report['columns'] as $col)
                <th class="{{ $col['type'] === 'text' ? '' : 'num' }}">{{ $col['label'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse ($report['rows'] as $row)
            <tr>
                @foreach ($report['columns'] as $i => $col)
                    <td class="{{ $col['type'] === 'text' ? '' : 'num' }}">{{ \App\Services\ReportBuilder::format($row[$i] ?? null, $col['type']) }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($report['columns']) }}" class="empty">No records found for this report.</td></tr>
        @endforelse
    </tbody>
    @if (! empty($report['footer']) && count($report['rows']) > 0)
        <tfoot>
            <tr>
                @foreach ($report['columns'] as $i => $col)
                    <td class="{{ $col['type'] === 'text' ? '' : 'num' }}">{{ \App\Services\ReportBuilder::format($report['footer'][$i] ?? null, $col['type']) }}</td>
                @endforeach
            </tr>
        </tfoot>
    @endif
</table>

<div class="doc-foot">OSAM Ukay-Ukay &middot; {{ $report['title'] }} &middot; Generated {{ $report['generated'] }}</div>
