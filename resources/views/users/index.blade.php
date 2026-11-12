@extends('layouts.app')
@section('title', 'User Management')
@section('page-title', 'User Management')

@section('content')
    <div class="page-head">
        <div>
            <div class="eyebrow">ADMINISTRATION</div>
            <h2 class="section-heading">User Accounts</h2>
            <p class="page-subtitle">Manage access, roles, and account status for your store team.</p>
        </div>
        <button type="button" class="btn btn-berry" data-bs-toggle="modal" data-bs-target="#userModal"><i class="fa-solid fa-user-plus me-2"></i>Add User</button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><x-stat-card icon="fa-users" label="Total Users" :value="number_format($totalUsers)" hint="All accounts" /></div>
        <div class="col-6 col-xl-3"><x-stat-card icon="fa-user-check" label="Active" :value="number_format($activeUsers)" hint="Can sign in" tone="rose" /></div>
        <div class="col-6 col-xl-3"><x-stat-card icon="fa-user-lock" label="Deactivated" :value="number_format($inactiveUsers)" hint="Cannot sign in" tone="dark" /></div>
        <div class="col-6 col-xl-3"><x-stat-card icon="fa-user-shield" label="Administrators" :value="number_format($adminCount)" hint="Full access" tone="warn" /></div>
    </div>

    <form method="GET" action="{{ route('users.index') }}" class="card-box filter-box mb-3">
        <div class="filter-heading"><span><i class="fa-solid fa-sliders me-2"></i>Filter directory</span><small>Find a team member quickly</small></div>
        <div class="row g-2 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label small">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Name, username or email">
            </div>
            <div class="col-lg-2 col-6">
                <label class="form-label small">Role</label>
                <select name="role" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="admin" @selected(request('role') === 'admin')>Admin</option>
                    <option value="staff" @selected(request('role') === 'staff')>Staff</option>
                </select>
            </div>
            <div class="col-lg-2 col-6">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Deactivated</option>
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-berry btn-sm px-3"><i class="fa-solid fa-magnifying-glass me-1"></i>Search</button>
                <a href="{{ route('users.index') }}" class="btn btn-light btn-sm px-3">Reset</a>
            </div>
        </div>
    </form>

    <div class="card-box p-0 overflow-hidden">
        @if ($users->isEmpty())
            <div class="empty-state py-5"><i class="fa-solid fa-users"></i>No users match your search.</div>
        @else
            <div class="table-responsive">
                <table class="table table-berry align-middle mb-0">
                    <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Date Created</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    @foreach ($users as $u)
                        @php $isSelf = $u->id === auth()->id(); @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar">{{ strtoupper(mb_substr($u->name, 0, 1)) }}</div>
                                    <div>
                                        <div class="fw-medium">{{ $u->name }} @if ($isSelf)<small class="text-muted">(you)</small>@endif</div>
                                        <small class="text-muted">{{ '@'.$u->username }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $u->email }}</td>
                            <td><span class="pill {{ $u->isAdmin() ? 'pill-berry' : 'pill-warn' }}">{{ ucfirst($u->role) }}</span></td>
                            <td><span class="pill {{ $u->is_active ? 'pill-ok' : 'pill-danger' }}">{{ $u->is_active ? 'Active' : 'Deactivated' }}</span></td>
                            <td class="text-nowrap">{{ $u->created_at->format('M d, Y') }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('users.show', $u) }}" class="btn-action" title="View details"><i class="fa-regular fa-eye"></i></a>
                                <button type="button" class="btn-action js-edit" title="Edit" data-bs-toggle="modal" data-bs-target="#userModal"
                                        data-action="{{ route('users.update', $u) }}" data-name="{{ $u->name }}" data-username="{{ $u->username }}"
                                        data-email="{{ $u->email }}" data-role="{{ $u->role }}" data-self="{{ $isSelf ? '1' : '0' }}"><i class="fa-regular fa-pen-to-square"></i></button>
                                <button type="button" class="btn-action js-reset" title="Reset password" data-bs-toggle="modal" data-bs-target="#resetModal"
                                        data-action="{{ route('users.password', $u) }}" data-name="{{ $u->name }}"><i class="fa-solid fa-key"></i></button>
                                @unless ($isSelf)
                                    <form method="POST" action="{{ route('users.status', $u) }}" class="d-inline"
                                          data-confirm-title="{{ $u->is_active ? 'Deactivate account?' : 'Activate account?' }}"
                                          data-confirm-text="{{ $u->is_active ? 'This person will be signed out and will not be able to log in until the account is activated again.' : 'This person will be able to log in again.' }}"
                                          data-confirm-name="{{ $u->name }}" data-confirm-ok="{{ $u->is_active ? 'Yes, deactivate' : 'Yes, activate' }}"
                                          data-confirm-variant="{{ $u->is_active ? 'danger' : 'berry' }}" data-confirm-icon="{{ $u->is_active ? 'fa-user-lock' : 'fa-user-check' }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn-action {{ $u->is_active ? 'danger' : '' }}" title="{{ $u->is_active ? 'Deactivate' : 'Activate' }}"><i class="fa-solid {{ $u->is_active ? 'fa-user-lock' : 'fa-user-check' }}"></i></button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">Showing {{ $users->firstItem() }}-{{ $users->lastItem() }} of {{ $users->total() }}</small>
                    {{ $users->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Add / Edit user --}}
    <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="userForm" class="modal-content js-submit" style="border-radius:16px" action="{{ route('users.store') }}" data-confirm-dynamic data-confirm-variant="berry">
                @csrf
                <input type="hidden" name="_method" id="userMethod" value="POST">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" id="userModalTitle">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="uName" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" id="uName" name="name" maxlength="100" required value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="uUsername" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" id="uUsername" name="username" maxlength="50" required autocomplete="off" value="{{ old('username') }}" class="form-control @error('username') is-invalid @enderror">
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="uRole" class="form-label">Role <span class="text-danger">*</span></label>
                            <select id="uRole" name="role" required class="form-select @error('role') is-invalid @enderror">
                                <option value="staff" @selected(old('role', 'staff') === 'staff')>Staff</option>
                                <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                            </select>
                            <input type="hidden" name="role" id="uRoleHidden" value="" disabled>
                            @error('role')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <div class="form-text d-none" id="uRoleNote">You cannot change your own role.</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="uEmail" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" id="uEmail" name="email" maxlength="255" required value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div id="passwordFields">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="uPassword" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" id="uPassword" name="password" minlength="8" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror">
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="uPasswordConfirm" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" id="uPasswordConfirm" name="password_confirmation" minlength="8" autocomplete="new-password" class="form-control">
                            </div>
                        </div>
                        <div class="form-text">At least 8 characters. Only administrators can create accounts, there is no public registration.</div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-berry px-4" id="userSubmit">Create Account</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Reset password --}}
    <div class="modal fade" id="resetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="resetForm" class="modal-content js-submit" style="border-radius:16px" action="" data-confirm-dynamic data-confirm-variant="berry">
                @csrf
                @method('PUT')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Set a new password for <strong id="resetName"></strong>. They will be signed out of any open session.</p>
                    <div class="mb-3">
                        <label for="rPassword" class="form-label">New Password <span class="text-danger">*</span></label>
                        <input type="password" id="rPassword" name="password" minlength="8" required autocomplete="new-password" class="form-control @error('password', 'reset') is-invalid @enderror">
                        @error('password', 'reset')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="rPasswordConfirm" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" id="rPasswordConfirm" name="password_confirmation" minlength="8" required autocomplete="new-password" class="form-control">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-berry px-4">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var storeUrl = @json(route('users.store'));
    var modalEl = document.getElementById('userModal');
    var form = document.getElementById('userForm');
    var f = {
        name: document.getElementById('uName'), username: document.getElementById('uUsername'),
        email: document.getElementById('uEmail'), role: document.getElementById('uRole'),
        roleHidden: document.getElementById('uRoleHidden'), roleNote: document.getElementById('uRoleNote'),
        pwBox: document.getElementById('passwordFields'),
        pw: document.getElementById('uPassword'), pwc: document.getElementById('uPasswordConfirm')
    };

    function setMode(mode, action, isSelf) {
        var edit = mode === 'edit';
        form.action = edit ? action : storeUrl;
        document.getElementById('userMethod').value = edit ? 'PUT' : 'POST';
        document.getElementById('userModalTitle').textContent = edit ? 'Edit User' : 'Add User';
        document.getElementById('userSubmit').textContent = edit ? 'Save Changes' : 'Create Account';

        // Passwords are only entered when creating an account.
        f.pwBox.classList.toggle('d-none', edit);
        f.pw.disabled = edit; f.pwc.disabled = edit;
        f.pw.required = !edit; f.pwc.required = !edit;

        // Your own role is locked.
        var lock = edit && isSelf;
        f.role.disabled = lock;
        f.roleHidden.disabled = !lock;
        f.roleHidden.value = f.role.value;
        f.roleNote.classList.toggle('d-none', !lock);
    }

    modalEl.addEventListener('show.bs.modal', function (e) {
        var b = e.relatedTarget;
        if (!b) { return; } // opened by script after a validation error
        form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
        f.pw.value = ''; f.pwc.value = '';

        if (b.classList.contains('js-edit')) {
            f.name.value = b.dataset.name; f.username.value = b.dataset.username;
            f.email.value = b.dataset.email; f.role.value = b.dataset.role;
            setMode('edit', b.dataset.action, b.dataset.self === '1');
        } else {
            f.name.value = ''; f.username.value = ''; f.email.value = ''; f.role.value = 'staff';
            setMode('create');
        }
    });
    modalEl.addEventListener('shown.bs.modal', function () { f.name.focus(); });

    form.addEventListener('confirm:prepare', function () {
        var edit = document.getElementById('userMethod').value === 'PUT';
        form.dataset.confirmTitle = edit ? 'Save changes?' : 'Create this account?';
        form.dataset.confirmText = edit ? 'The account details will be updated.' : 'The person will be able to log in with this username and password.';
        form.dataset.confirmName = f.name.value.trim();
        form.dataset.confirmOk = edit ? 'Yes, save' : 'Yes, create';
        form.dataset.confirmIcon = edit ? 'fa-floppy-disk' : 'fa-user-plus';
    });

    // Reset password popup
    var resetModal = document.getElementById('resetModal');
    var resetForm = document.getElementById('resetForm');
    resetModal.addEventListener('show.bs.modal', function (e) {
        var b = e.relatedTarget;
        if (!b) { return; }
        resetForm.action = b.dataset.action;
        document.getElementById('resetName').textContent = b.dataset.name;
        resetForm.dataset.who = b.dataset.name;
        resetForm.querySelectorAll('input[type=password]').forEach(function (i) { i.value = ''; i.classList.remove('is-invalid'); });
    });
    resetModal.addEventListener('shown.bs.modal', function () { document.getElementById('rPassword').focus(); });
    resetForm.addEventListener('confirm:prepare', function () {
        resetForm.dataset.confirmTitle = 'Reset this password?';
        resetForm.dataset.confirmText = 'The old password will stop working immediately.';
        resetForm.dataset.confirmName = resetForm.dataset.who || '';
        resetForm.dataset.confirmOk = 'Yes, reset';
        resetForm.dataset.confirmIcon = 'fa-key';
    });

    @if ($errors->any() && session('open_modal'))
        @if (session('open_modal') === 'reset')
            resetForm.action = @json(route('users.password', session('edit_id')));
            @php $resetUser = \App\Models\User::find(session('edit_id')); @endphp
            document.getElementById('resetName').textContent = @json($resetUser?->name);
            resetForm.dataset.who = @json($resetUser?->name);
            new bootstrap.Modal(resetModal).show();
        @elseif (session('open_modal') === 'edit')
            @php $editUser = \App\Models\User::find(session('edit_id')); @endphp
            setMode('edit', @json(route('users.update', session('edit_id'))), @json($editUser?->id === auth()->id()));
            new bootstrap.Modal(modalEl).show();
        @else
            setMode('create');
            new bootstrap.Modal(modalEl).show();
        @endif
    @endif
})();
</script>
@endpush
