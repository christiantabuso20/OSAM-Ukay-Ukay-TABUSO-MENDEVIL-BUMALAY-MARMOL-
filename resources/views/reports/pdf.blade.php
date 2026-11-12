<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    <style>
        @page{margin:28px 26px}
        body{font-family:'DejaVu Sans',sans-serif;color:#332A2D;font-size:9px}
        .doc-head{border-bottom:3px solid #713B50;padding-bottom:8px;margin-bottom:10px}
        .brand{font-size:20px;color:#713B50;font-weight:bold}
        .system{color:#8C8587;font-size:8px;margin-bottom:6px}
        h1{font-size:14px;margin:4px 0;color:#482735}
        .meta{font-size:8.5px;margin-top:2px}
        table{width:100%;border-collapse:collapse}
        table.summary{width:55%;margin-bottom:10px;border:1px solid #E8DCCB}
        table.summary td{padding:4px 8px;border-bottom:1px solid #F5F0E8}
        table.summary td.lbl{background:#F5F0E8;color:#482735;font-weight:bold}
        table.summary td.val{text-align:right;font-weight:bold}
        table.data th{background:#713B50;color:#ffffff;text-align:left;padding:5px 6px;font-size:8px}
        table.data td{padding:4px 6px;border-bottom:1px solid #E8DCCB;font-size:8px;vertical-align:top}
        table.data tfoot td{font-weight:bold;background:#F5F0E8;border-top:2px solid #713B50}
        .num{text-align:right}
        .empty{text-align:center;color:#8C8587;padding:14px}
        .doc-foot{margin-top:12px;text-align:center;color:#8C8587;font-size:7.5px}
    </style>
</head>
<body>
    @include('reports._document', ['report' => $report])
</body>
</html>
