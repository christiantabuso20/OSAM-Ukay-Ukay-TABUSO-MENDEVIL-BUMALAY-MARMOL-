<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\ActivityLog;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** User management. Every route here is behind the admin-only middleware. */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $role = $request->query('role');
        $status = $request->query('status');

        $users = User::query()
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when(in_array($role, [User::ROLE_ADMIN, User::ROLE_STAFF], true), fn ($q) => $q->where('role', $role))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'totalUsers' => User::count(),
            'activeUsers' => User::where('is_active', true)->count(),
            'inactiveUsers' => User::where('is_active', false)->count(),
            'adminCount' => User::where('role', User::ROLE_ADMIN)->count(),
        ]);
    }

    public function show(User $user): View
    {
        return view('users.show', [
            'account' => $user,
            'salesCount' => Sale::where('user_id', $user->id)->count(),
            'salesTotal' => (float) Sale::where('user_id', $user->id)->sum('total_amount'),
            'movementCount' => StockMovement::where('user_id', $user->id)->count(),
            'activities' => ActivityLog::where('user_id', $user->id)->latest()->latest('id')->limit(10)->get(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'], // hashed by the model cast
                'role' => $data['role'],
                'is_active' => true,
            ]);

            ActivityLog::record('Users', 'create', "Created {$user->role} account {$user->name} ({$user->username}).");

            return $user;
        });

        return redirect()->route('users.index')->with('success', "Account for {$user->name} was created.");
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // Admins cannot change their own role (this prevents locking the system out of administrators).
        if ($user->is($request->user()) && $data['role'] !== $user->role) {
            session()->flash('open_modal', 'edit');
            session()->flash('edit_id', $user->id);

            throw ValidationException::withMessages(['role' => 'You cannot change your own role.']);
        }

        DB::transaction(function () use ($user, $data) {
            $roleChanged = $user->role !== $data['role'];

            $user->update([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'role' => $data['role'],
            ]);

            ActivityLog::record('Users', 'update', "Updated account {$user->name} ({$user->username})".($roleChanged ? ", role is now {$user->role}." : '.'));
        });

        return redirect()->route('users.index')->with('success', "Account for {$user->name} was updated.");
    }

    /** Activate or deactivate an account. */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $activate = ! $user->is_active;

        DB::transaction(function () use ($user, $activate) {
            $user->update(['is_active' => $activate]);

            if (! $activate) {
                $this->endSessionsOf($user);
            }

            ActivityLog::record('Users', $activate ? 'activate' : 'deactivate', ($activate ? 'Activated' : 'Deactivated')." account {$user->name} ({$user->username}).");
        });

        return back()->with('success', "Account for {$user->name} was ".($activate ? 'activated.' : 'deactivated.'));
    }

    /** Administrator sets a new password for someone. */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'reset')
                ->with('open_modal', 'reset')
                ->with('edit_id', $user->id);
        }

        DB::transaction(function () use ($request, $user) {
            $user->forceFill([
                'password' => $request->input('password'),
                'remember_token' => Str::random(60),
            ])->save();

            // Sign the person out everywhere so only the new password works.
            if (! $user->is($request->user())) {
                $this->endSessionsOf($user);
            }

            ActivityLog::record('Users', 'reset_password', "Reset the password of {$user->name} ({$user->username}).");
        });

        return back()->with('success', "Password for {$user->name} was reset.");
    }

    /** Remove stored sessions so a deactivated / reset account is signed out right away. */
    private function endSessionsOf(User $user): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
    }
}
