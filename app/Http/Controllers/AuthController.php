<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Please enter your username.',
            'password.required' => 'Please enter your password.',
        ]);

        $throttleKey = Str::lower($credentials['username']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withInput($request->only('username'))
                ->withErrors(['username' => "Too many login attempts. Try again in {$seconds} seconds."]);
        }

        // Only active accounts may sign in.
        if (Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate(); // new session id after login

            $user = Auth::user();
            ActivityLog::record('Authentication', 'login', 'Logged in to the system.', $user);

            return redirect()->route($user->dashboardRoute());
        }

        RateLimiter::hit($throttleKey);

        // Give a clearer message when the password is right but the account is disabled.
        $user = User::where('username', $credentials['username'])->first();
        if ($user && ! $user->is_active && Hash::check($credentials['password'], $user->password)) {
            return back()->withInput($request->only('username'))
                ->withErrors(['username' => 'Your account has been deactivated. Please contact the administrator.']);
        }

        return back()->withInput($request->only('username'))
            ->withErrors(['username' => 'Invalid username or password.']);
    }

    public function logout(Request $request): RedirectResponse
    {
        if ($user = Auth::user()) {
            ActivityLog::record('Authentication', 'logout', 'Logged out of the system.', $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been logged out.');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Same message whether or not the email exists (prevents account guessing).
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'If that email is registered, a password reset link has been sent.');
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Your password has been reset. You can now log in.')
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
