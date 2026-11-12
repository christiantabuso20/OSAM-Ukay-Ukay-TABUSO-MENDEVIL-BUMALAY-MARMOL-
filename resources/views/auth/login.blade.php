@extends('layouts.auth')
@section('title', 'Login')

@section('content')
    <h3 class="auth-title">Welcome Back!</h3>
    <p class="auth-subtitle">Sign in to your account to continue</p>

    @if (session('status'))
        <div class="alert alert-success py-2"><i class="fa-solid fa-circle-check me-2"></i>{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger py-2"><i class="fa-solid fa-circle-exclamation me-2"></i>{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.attempt') }}" id="loginForm">
        @csrf

        <div class="mb-3">
            <label for="username" class="form-label"><i class="fa-solid fa-user"></i>Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-regular fa-user"></i></span>
                <input type="text" name="username" id="username" value="{{ old('username') }}"
                       class="form-control @error('username') is-invalid @enderror" placeholder="Enter your username"
                       required autofocus autocomplete="username" autocapitalize="none" spellcheck="false">
            </div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label"><i class="fa-solid fa-lock"></i>Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                <input type="password" name="password" id="password"
                       class="form-control @error('password') is-invalid @enderror" placeholder="Enter your password"
                       required autocomplete="current-password">
                <button type="button" class="btn btn-toggle" id="togglePassword" aria-label="Show or hide password">
                    <i class="fa-regular fa-eye"></i>
                </button>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
            <a href="{{ route('password.request') }}" class="auth-link">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-berry w-100">
            <span class="btn-label"><i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Login</span>
            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
            <span class="btn-loading d-none">Signing in...</span>
        </button>
    </form>
@endsection
