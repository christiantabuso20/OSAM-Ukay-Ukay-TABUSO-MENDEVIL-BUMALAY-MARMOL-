@extends('layouts.app')
@section('title', 'Edit Product')
@section('page-title', 'Clothing Inventory')

@section('content')
    <div class="page-head">
        <div><h2 class="section-heading">Edit Product</h2><p class="text-muted mb-0">{{ $product->name }} &middot; {{ $product->sku }}</p></div>
        <a href="{{ route('products.show', $product) }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-2"></i>Back</a>
    </div>
    <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" class="js-submit"
          data-confirm-title="Save changes?" data-confirm-text="The product details will be updated." data-confirm-name="{{ $product->name }}" data-confirm-ok="Yes, save" data-confirm-variant="berry" data-confirm-icon="fa-floppy-disk">
        @csrf
        @method('PUT')
        @include('inventory._form')
    </form>
@endsection
