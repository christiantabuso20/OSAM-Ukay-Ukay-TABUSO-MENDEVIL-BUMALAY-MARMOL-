<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $report['title'] }} | OSAM Ukay-Ukay</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root{--berry:#713B50;--berry-dark:#482735;--rose:#B98596;--vanilla:#F5F0E8;--beige:#E8DCCB;--text:#332A2D;--muted:#8C8587}
        *{box-sizing:border-box}
        body{font-family:'Poppins',Arial,sans-serif;background:var(--vanilla);color:var(--text);margin:0;padding:1.25rem;font-size:12px}
        .toolbar{max-width:1100px;margin:0 auto 1rem;display:flex;gap:.5rem;flex-wrap:wrap;align-items:center}
        .btn{display:inline-block;border:1px solid var(--beige);background:#fff;color:var(--text);border-radius:10px;padding:.45rem 1rem;font:500 13px 'Poppins',Arial,sans-serif;text-decoration:none;cursor:pointer}
        .btn-berry{background:var(--berry);border-color:var(--berry);color:#fff}
        .notice{max-width:1100px;margin:0 auto 1rem;background:#fff;border:1px solid var(--beige);border-left:4px solid var(--berry);border-radius:10px;padding:.65rem 1rem;font-size:12px}
        .sheet{max-width:1100px;margin:0 auto;background:#fff;border:1px solid var(--beige);border-radius:14px;padding:1.75rem}
        .doc-head{border-bottom:3px solid var(--berry);padding-bottom:.9rem;margin-bottom:1.1rem}
        .brand{font-family:'Playfair Display',serif;font-size:26px;color:var(--berry);font-weight:700}
        .system{color:var(--muted);font-size:11px;margin-bottom:.6rem}
        h1{font-size:18px;margin:.3rem 0 .4rem;color:var(--berry-dark)}
        .meta{font-size:11.5px;color:var(--text);margin-top:2px}
        table{width:100%;border-collapse:collapse}
        table.summary{width:auto;min-width:340px;margin-bottom:1.1rem;border:1px solid var(--beige)}
        table.summary td{padding:5px 12px;border-bottom:1px solid var(--vanilla)}
        table.summary td.lbl{background:var(--vanilla);color:var(--berry-dark);font-weight:600}
        table.summary td.val{text-align:right;font-weight:600}
        table.data th{background:var(--berry);color:#fff;text-align:left;padding:6px 8px;font-size:11px;font-weight:600}
        table.data td{padding:5px 8px;border-bottom:1px solid var(--beige);font-size:11px;vertical-align:top}
        table.data tbody tr:nth-child(even) td{background:#FBF8F3}
        table.data tfoot td{font-weight:700;background:var(--vanilla);border-top:2px solid var(--berry)}
        .num{text-align:right !important;white-space:nowrap}
        .empty{text-align:center;color:var(--muted);padding:1.5rem}
        .doc-foot{margin-top:1.2rem;text-align:center;color:var(--muted);font-size:10px}
        @page{size:A4 {{ count($report['columns']) > 7 ? 'landscape' : 'portrait' }};margin:12mm}
        @media print{
            body{background:#fff;padding:0}
            .toolbar,.notice{display:none !important}
            .sheet{border:0;border-radius:0;padding:0;max-width:none}
            *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
            thead{display:table-header-group}
            tr{page-break-inside:avoid}
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="btn btn-berry" onclick="window.print()">Print</button>
        <a class="btn" href="javascript:history.back()">Back to Reports</a>
    </div>

    @if (! empty($pdfFallback))
        <div class="notice">
            The print window opens automatically. To get a PDF file, choose <strong>Save as PDF</strong> as the printer/destination.
            (Direct PDF download is available once the <code>dompdf/dompdf</code> package is installed.)
        </div>
    @endif

    <div class="sheet">
        @include('reports._document', ['report' => $report])
    </div>

    @if (! empty($autoPrint))
        <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 500); });</script>
    @endif
</body>
</html>
