@extends('layouts.auth')
@section('title', 'Forgot Password')

@section('card-icon', 'fa-key')

@section('content')
    <h3 class="auth-title">Forgot password?</h3>
    <p class="auth-subtitle">Enter your email and we will send you a reset link.</p>

    @if (session('status'))
        <div class="alert alert-success py-2"><i class="fa-solid fa-circle-check me-2"></i>{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger py-2"><i class="fa-solid fa-circle-exclamation me-2"></i>{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-4">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
                <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-control" required autofocus>
            </div>
        </div>
        <button type="submit" class="btn btn-berry w-100">
            <span class="btn-label">Send Reset Link</span>
            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
            <span class="btn-loading d-none">Sending...</span>
        </button>
    </form>
    <p class="text-center mt-3 mb-0"><a href="{{ route('login') }}" class="auth-link"><i class="fa-solid fa-arrow-left me-1"></i>Back to login</a></p>
@endsection
