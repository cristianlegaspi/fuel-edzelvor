<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Superadmin-only. Also gated by the 'role:superadmin' route middleware
 * (routes/web.php) and by UserPolicy — three independent layers, so a
 * bug in any one of them doesn't hand out account-management access.
 *
 * The role field is the sensitive one here: it's always validated
 * against User::ROLES server-side (Rule::in), never trusted as free
 * text, so a request can't smuggle in an unrecognised or elevated role
 * value that bypasses the app's RBAC checks elsewhere.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $this->authorize('create', User::class);

        $user = new User(['role' => User::ROLE_STAFF, 'is_active' => true]);

        return view('users.create', compact('user'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('users.index')
            ->with('success', "User \"{$user->name}\" created.");
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);

        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $isActive = $request->boolean('is_active', true);

        // A superadmin can't demote or deactivate their own account —
        // that's how people get accidentally locked out. Force those
        // two fields back to their current values when editing yourself.
        if ($user->is($request->user())) {
            $validated['role'] = User::ROLE_SUPERADMIN;
            $isActive = true;
        }

        // Never leave the app with zero active superadmins.
        $wouldLoseLastSuperAdmin = $user->isSuperAdmin()
            && ($validated['role'] !== User::ROLE_SUPERADMIN || ! $isActive)
            && User::query()->where('role', User::ROLE_SUPERADMIN)->where('is_active', true)->count() <= 1;

        if ($wouldLoseLastSuperAdmin) {
            return back()->withErrors([
                'role' => 'At least one active super admin must remain.',
            ])->withInput();
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_active' => $isActive,
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()->route('users.index')
            ->with('success', "User \"{$user->name}\" updated.");
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);

        if (User::query()->where('role', User::ROLE_SUPERADMIN)->count() <= 1 && $user->isSuperAdmin()) {
            return back()->withErrors(['email' => 'At least one super admin must remain.']);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted.');
    }
}
