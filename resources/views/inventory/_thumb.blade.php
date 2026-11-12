@php $size = $size ?? 46; @endphp
@if ($product->image_url)
    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="thumb" style="width:{{ $size }}px;height:{{ $size }}px">
@else
    <span class="thumb thumb-empty" style="width:{{ $size }}px;height:{{ $size }}px"><i class="fa-solid fa-shirt"></i></span>
@endif
