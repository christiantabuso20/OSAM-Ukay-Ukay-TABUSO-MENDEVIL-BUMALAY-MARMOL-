@php $editing = $product->exists; @endphp

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card-box">
            <div class="card-box-header"><h3>Product Information</h3></div>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="name">Product Name <span class="text-danger">*</span></label>
                    <input type="text" id="name" name="name" maxlength="150" required value="{{ old('name', $product->name) }}" class="form-control @error('name') is-invalid @enderror">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="category_id">Category <span class="text-danger">*</span></label>
                    <select id="category_id" name="category_id" required class="form-select @error('category_id') is-invalid @enderror">
                        <option value="">Select category</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="clothing_type">Clothing Type <span class="text-danger">*</span></label>
                    <select id="clothing_type" name="clothing_type" required class="form-select @error('clothing_type') is-invalid @enderror">
                        <option value="">Select type</option>
                        @foreach ($clothingTypes as $t)
                            <option value="{{ $t }}" @selected(old('clothing_type', $product->clothing_type) === $t)>{{ $t }}</option>
                        @endforeach
                    </select>
                    @error('clothing_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="size">Size <span class="text-danger">*</span></label>
                    <select id="size" name="size" required class="form-select @error('size') is-invalid @enderror">
                        <option value="">Select size</option>
                        @foreach ($sizes as $s)
                            <option value="{{ $s }}" @selected(old('size', $product->size) === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                    @error('size')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="condition">Condition <span class="text-danger">*</span></label>
                    <select id="condition" name="condition" required class="form-select @error('condition') is-invalid @enderror">
                        <option value="">Select condition</option>
                        @foreach ($conditions as $c)
                            <option value="{{ $c }}" @selected(old('condition', $product->condition) === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                    @error('condition')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" rows="3" maxlength="1000" class="form-control @error('description') is-invalid @enderror">{{ old('description', $product->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="card-box mt-4">
            <div class="card-box-header"><h3>Pricing and Stock</h3></div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="unit_price">Unit Price (₱) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" max="999999.99" id="unit_price" name="unit_price" required value="{{ old('unit_price', $product->unit_price) }}" class="form-control @error('unit_price') is-invalid @enderror">
                    @error('unit_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                @unless ($editing)
                    <div class="col-md-4">
                        <label class="form-label" for="initial_stock">Initial Stock <span class="text-danger">*</span></label>
                        <input type="number" step="1" min="0" max="100000" id="initial_stock" name="initial_stock" required value="{{ old('initial_stock', 0) }}" class="form-control @error('initial_stock') is-invalid @enderror">
                        @error('initial_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endunless

                <div class="col-md-4">
                    <label class="form-label" for="minimum_stock">Minimum Stock Level <span class="text-danger">*</span></label>
                    <input type="number" step="1" min="0" max="100000" id="minimum_stock" name="minimum_stock" required value="{{ old('minimum_stock', $product->minimum_stock ?? 5) }}" class="form-control @error('minimum_stock') is-invalid @enderror">
                    @error('minimum_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                @if ($editing)
                    <div class="col-12">
                        <div class="note-box"><i class="fa-solid fa-circle-info me-2"></i>Available stock is <strong>{{ $product->available_stock }}</strong>. To change it, use Stock Monitoring (Stock In, Stock Out, Damaged) so every movement is recorded.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-box">
            <div class="card-box-header"><h3>Product Image</h3></div>
            <div class="text-center mb-3">
                <img id="imagePreview" src="{{ $product->image_url }}" alt="Preview" class="preview-img {{ $product->image_url ? '' : 'd-none' }}">
                <div id="imagePlaceholder" class="preview-empty {{ $product->image_url ? 'd-none' : '' }}"><i class="fa-regular fa-image"></i><div>No image selected</div></div>
            </div>
            <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp" class="form-control @error('image') is-invalid @enderror">
            @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">JPG, PNG or WEBP, up to 2 MB.@if ($editing) Leave empty to keep the current image.@endif</div>
        </div>

        @if ($editing)
            <div class="card-box mt-4">
                <div class="small text-muted">SKU</div>
                <div class="fw-semibold">{{ $product->sku }}</div>
            </div>
        @else
            <div class="note-box mt-4"><i class="fa-solid fa-barcode me-2"></i>A unique SKU is generated automatically when the product is saved.</div>
        @endif
    </div>
</div>

<div class="d-flex gap-2 justify-content-end mt-4">
    <a href="{{ $editing ? route('products.show', $product) : route('products.index') }}" class="btn btn-light">Cancel</a>
    <button type="submit" class="btn btn-berry px-4"><i class="fa-solid fa-floppy-disk me-2"></i>{{ $editing ? 'Save Changes' : 'Save Product' }}</button>
</div>

@push('scripts')
<script>
document.getElementById('image').addEventListener('change', function () {
    var f = this.files[0];
    var img = document.getElementById('imagePreview');
    var ph = document.getElementById('imagePlaceholder');
    if (!f) { return; }
    if (f.size > 2 * 1024 * 1024) { alert('The image must not be larger than 2 MB.'); this.value = ''; return; }
    img.src = URL.createObjectURL(f);
    img.classList.remove('d-none');
    ph.classList.add('d-none');
});
</script>
@endpush
