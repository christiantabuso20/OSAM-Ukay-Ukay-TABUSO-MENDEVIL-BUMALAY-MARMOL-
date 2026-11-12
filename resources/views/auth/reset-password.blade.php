@extends('layouts.auth')
@section('title', 'Reset Password')

@section('card-icon', 'fa-key')

@section('content')
    <h3 class="auth-title">Reset password</h3>
    <p class="auth-subtitle">Choose a new password (at least 8 characters).</p>

    @if ($errors->any())
        <div class="alert alert-danger py-2"><i class="fa-solid fa-circle-exclamation me-2"></i>{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" name="email" id="email" value="{{ old('email', $email) }}" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <input type="password" name="password" id="password" class="form-control" required autocomplete="new-password">
        </div>
        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-berry w-100">
            <span class="btn-label">Reset Password</span>
            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
            <span class="btn-loading d-none">Saving...</span>
        </button>
    </form>
@endsection
