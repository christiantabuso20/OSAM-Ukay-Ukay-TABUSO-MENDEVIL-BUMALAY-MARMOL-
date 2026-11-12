@php
    $cls = ['in_stock' => 'pill-ok', 'low_stock' => 'pill-warn', 'out_of_stock' => 'pill-danger'][$product->stock_status];
@endphp
<span class="pill {{ $cls }}">{{ $product->stock_status_label }}</span>
