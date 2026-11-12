<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $sale->sale_number }} | OSAM Ukay-Ukay</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        :root{--berry:#713B50;--berry-dark:#482735;--rose:#B98596;--vanilla:#F5F0E8;--beige:#E8DCCB;--text:#332A2D;--muted:#8C8587}
        body{font-family:'Poppins',sans-serif;background:var(--vanilla);color:var(--text);min-height:100vh;padding:1.5rem 1rem}
        .receipt-actions{max-width:380px;margin:0 auto 1rem;display:flex;gap:.5rem;flex-wrap:wrap}
        .receipt-actions .btn{border-radius:10px;font-weight:500;font-size:.9rem}
        .btn-berry{background:var(--berry);border-color:var(--berry);color:#fff}
        .btn-berry:hover{background:var(--berry-dark);border-color:var(--berry-dark);color:#fff}
        .btn-light{background:#fff;border:1px solid var(--beige)}
        .receipt{max-width:380px;margin:0 auto;background:#fff;border-radius:16px;box-shadow:0 8px 30px rgba(72,39,53,.1);padding:1.75rem 1.5rem;font-size:.85rem}
        .receipt-brand{text-align:center;border-bottom:2px dashed var(--beige);padding-bottom:1rem;margin-bottom:1rem}
        .receipt-brand h1{font-family:'Playfair Display',serif;font-size:1.5rem;color:var(--berry);margin:0}
        .receipt-brand small{color:var(--muted)}
        .receipt-meta{display:grid;grid-template-columns:auto 1fr;gap:.15rem .75rem;margin-bottom:1rem}
        .receipt-meta dt{font-weight:400;color:var(--muted)}
        .receipt-meta dd{margin:0;text-align:right;font-weight:500}
        .receipt table{width:100%}
        .receipt th{font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);font-weight:500;border-bottom:1px solid var(--beige);padding-bottom:.35rem}
        .receipt td{padding:.4rem 0;vertical-align:top;border-bottom:1px dotted var(--beige)}
        .receipt .num{text-align:right;white-space:nowrap}
        .receipt-totals{margin-top:.9rem;border-top:2px dashed var(--beige);padding-top:.75rem}
        .receipt-totals div{display:flex;justify-content:space-between;padding:.15rem 0}
        .receipt-totals .grand{font-size:1.05rem;font-weight:600;color:var(--berry-dark)}
        .receipt-foot{text-align:center;margin-top:1.1rem;color:var(--muted);font-size:.78rem}
        @media print{
            body{background:#fff;padding:0}
            .no-print{display:none !important}
            .receipt{box-shadow:none;border-radius:0;max-width:100%;padding:.25rem}
        }
    </style>
</head>
<body>
    <div class="receipt-actions no-print">
        <button type="button" class="btn btn-berry" onclick="window.print()"><i class="fa-solid fa-print me-2"></i>Print</button>
        <a href="{{ route('sales.create') }}" class="btn btn-light"><i class="fa-solid fa-plus me-2"></i>New Sale</a>
        <a href="{{ route('sales.index') }}" class="btn btn-light"><i class="fa-solid fa-list me-2"></i>Sales History</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success py-2 no-print" style="max-width:380px;margin:0 auto 1rem;border-radius:12px;font-size:.88rem"><i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}</div>
    @endif

    <div class="receipt">
        <div class="receipt-brand">
            <h1>OSAM Ukay-Ukay</h1>
            <small>Sales Receipt</small>
        </div>

        <dl class="receipt-meta">
            <dt>Sales ID</dt><dd>{{ $sale->sale_number }}</dd>
            <dt>Date</dt><dd>{{ $sale->created_at->format('M d, Y h:i A') }}</dd>
            <dt>Staff</dt><dd>{{ $sale->staff_name }}</dd>
        </dl>

        <table>
            <thead><tr><th>Item</th><th class="num">Qty</th><th class="num">Price</th><th class="num">Subtotal</th></tr></thead>
            <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>{{ $item->product_name }}<br><small class="text-muted">{{ $item->sku }}</small></td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">₱{{ number_format($item->unit_price, 2) }}</td>
                    <td class="num">₱{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="receipt-totals">
            <div><span>Total Items</span><span>{{ $sale->items->sum('quantity') }}</span></div>
            <div class="grand"><span>Total Amount</span><span>₱{{ number_format($sale->total_amount, 2) }}</span></div>
            <div><span>Payment Received</span><span>₱{{ number_format($sale->payment_amount, 2) }}</span></div>
            <div><span>Change</span><span>₱{{ number_format($sale->change_amount, 2) }}</span></div>
        </div>

        <p class="receipt-foot mb-0">Thank you for shopping at OSAM Ukay-Ukay!<br>Please keep this receipt for your records.</p>
    </div>

    @if (request()->boolean('print'))
        <script>window.addEventListener('load', function () { window.print(); });</script>
    @endif
</body>
</html>
