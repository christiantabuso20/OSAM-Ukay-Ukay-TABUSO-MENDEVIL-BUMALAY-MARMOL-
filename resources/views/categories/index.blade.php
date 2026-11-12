@extends('layouts.app')
@section('title', 'Category Management')
@section('page-title', 'Category Management')

@section('content')
    <div class="page-head">
        <div><h2 class="section-heading">Categories</h2></div>
        <button type="button" class="btn btn-berry" data-bs-toggle="modal" data-bs-target="#categoryModal" id="addCategoryBtn"><i class="fa-solid fa-plus me-2"></i>Add Category</button>
    </div>

    <form method="GET" action="{{ route('categories.index') }}" class="card-box filter-box mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-6 col-lg-4">
                <label class="form-label small">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Category name or description">
            </div>
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-berry btn-sm px-3"><i class="fa-solid fa-magnifying-glass me-1"></i>Search</button>
                <a href="{{ route('categories.index') }}" class="btn btn-light btn-sm px-3">Reset</a>
            </div>
        </div>
    </form>

    <div class="card-box p-0 overflow-hidden">
        @if ($categories->isEmpty())
            <div class="empty-state py-5"><i class="fa-solid fa-tags"></i>
                {{ request('search') ? 'No categories match your search.' : 'No categories yet. Click "Add Category" to create one.' }}
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-berry align-middle mb-0">
                    <thead><tr><th>Category</th><th>Description</th><th class="text-center">Products</th><th>Date Added</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td class="fw-medium">{{ $category->name }}</td>
                            <td class="text-muted">{{ \Illuminate\Support\Str::limit($category->description, 70) ?: '-' }}</td>
                            <td class="text-center"><span class="pill pill-ok">{{ $category->products_count }}</span></td>
                            <td class="text-nowrap">{{ $category->created_at->format('M d, Y') }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('categories.show', $category) }}" class="btn-action" title="View"><i class="fa-regular fa-eye"></i></a>
                                <button type="button" class="btn-action js-edit" title="Edit" data-bs-toggle="modal" data-bs-target="#categoryModal"
                                        data-action="{{ route('categories.update', $category) }}"
                                        data-name="{{ $category->name }}" data-description="{{ $category->description }}"><i class="fa-regular fa-pen-to-square"></i></button>
                                <form method="POST" action="{{ route('categories.destroy', $category) }}" class="d-inline"
                                      data-confirm-title="Delete category?" data-confirm-text="This category will be permanently removed. This is only possible when no products use it."
                                      data-confirm-name="{{ $category->name }}" data-confirm-ok="Yes, delete" data-confirm-variant="danger" data-confirm-icon="fa-trash-can">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-action danger" title="Delete"><i class="fa-regular fa-trash-can"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($categories->hasPages())
                <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">Showing {{ $categories->firstItem() }}-{{ $categories->lastItem() }} of {{ $categories->total() }}</small>
                    {{ $categories->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Add / Edit modal --}}
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="categoryForm" class="modal-content js-submit" style="border-radius:16px" action="{{ route('categories.store') }}" data-confirm-dynamic data-confirm-variant="berry">
                @csrf
                <input type="hidden" name="_method" id="categoryMethod" value="POST">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" id="categoryModalTitle">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="catName" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" id="catName" name="name" maxlength="100" required value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="catDescription" class="form-label">Description</label>
                        <textarea id="catDescription" name="description" rows="3" maxlength="500" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-berry px-4" id="categorySubmit">Save Category</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
(function () {
    var storeUrl = @json(route('categories.store'));
    var modalEl = document.getElementById('categoryModal');
    var form = document.getElementById('categoryForm');
    var nameInput = document.getElementById('catName');
    var descInput = document.getElementById('catDescription');

    function setMode(mode, action) {
        var edit = mode === 'edit';
        form.action = edit ? action : storeUrl;
        document.getElementById('categoryMethod').value = edit ? 'PUT' : 'POST';
        document.getElementById('categoryModalTitle').textContent = edit ? 'Edit Category' : 'Add Category';
        document.getElementById('categorySubmit').textContent = edit ? 'Save Changes' : 'Save Category';
    }

    modalEl.addEventListener('show.bs.modal', function (e) {
        var b = e.relatedTarget;
        if (!b) { return; } // opened by script after a validation error
        form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
        if (b.classList.contains('js-edit')) {
            setMode('edit', b.dataset.action);
            nameInput.value = b.dataset.name;
            descInput.value = b.dataset.description || '';
        } else {
            setMode('create');
            nameInput.value = '';
            descInput.value = '';
        }
    });
    modalEl.addEventListener('shown.bs.modal', function () { nameInput.focus(); });

    // Text for the confirmation dialog (add vs. edit)
    form.addEventListener('confirm:prepare', function () {
        var edit = document.getElementById('categoryMethod').value === 'PUT';
        form.dataset.confirmTitle = edit ? 'Save changes?' : 'Add this category?';
        form.dataset.confirmText = edit ? 'The category details will be updated.' : 'The category will be added to your list.';
        form.dataset.confirmName = nameInput.value.trim();
        form.dataset.confirmOk = edit ? 'Yes, save' : 'Yes, add';
        form.dataset.confirmIcon = edit ? 'fa-floppy-disk' : 'fa-circle-plus';
    });

    @if ($errors->any() && session('open_modal'))
        // Re-open the modal after a failed save so the errors are visible.
        @if (session('open_modal') === 'edit')
            setMode('edit', @json(route('categories.update', session('edit_id'))));
        @else
            setMode('create');
        @endif
        new bootstrap.Modal(modalEl).show();
    @endif
})();
</script>
@endpush
