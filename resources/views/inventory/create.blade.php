@extends('layouts.app')
@section('title', 'Add Product')
@section('page-title', 'Clothing Inventory')

@section('content')
    <div class="page-head">
        <div><h2 class="section-heading">Add Product</h2></div>
        <a href="{{ route('products.index') }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-2"></i>Back</a>
    </div>
    <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="js-submit"
          data-confirm-title="Add this product?" data-confirm-text="The product will be registered in the inventory." data-confirm-ok="Yes, add" data-confirm-variant="berry" data-confirm-icon="fa-circle-plus">
        @csrf
        @include('inventory._form')
    </form>
@endsection
