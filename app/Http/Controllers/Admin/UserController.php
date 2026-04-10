<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::with('role')->orderBy('created_at', 'desc')->paginate(20);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->get()->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name_en' => $role->display_name_en,
                'display_name_ar' => $role->display_name_ar,
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'roles' => Role::orderBy('name')->get()->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name_en' => $role->display_name_en,
                'display_name_ar' => $role->display_name_ar,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'boolean',
        ]);

        $plainPassword = $data['password'];
        $user = User::create($data);

        AuditLogger::log('created', $user);

        return redirect()->route('admin.users.index')->with([
            'success' => 'User created successfully.',
            'credentials' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'password' => $plainPassword,
                'login_url' => url('/admin/login'),
            ],
        ]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Edit', [
            'editUser' => $user,
            'roles' => Role::orderBy('name')->get()->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name_en' => $role->display_name_en,
                'display_name_ar' => $role->display_name_ar,
                'permissions' => $role->permissions ?? [],
                'is_system' => $role->is_system,
            ]),
            'permissionModules' => config('permissions.modules'),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'boolean',
        ]);

        $plainPassword = $data['password'] ?? null;

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        AuditLogger::log('updated', $user);

        $flashData = ['success' => 'User updated successfully.'];

        // Flash updated credentials if password was changed or email/username updated
        $updatedCredentials = [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'login_url' => url('/admin/login'),
        ];
        if ($plainPassword) {
            $updatedCredentials['password'] = $plainPassword;
        }
        $flashData['credentials'] = $updatedCredentials;

        return redirect()->route('admin.users.index')->with($flashData);
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot delete your own account.');
        }

        AuditLogger::log('deleted', $user);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }
}
