<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(): Response
    {
        $roles = Role::withCount('users')->orderBy('is_system', 'desc')->orderBy('name')->get();

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Roles/Create', [
            'availablePermissions' => config('permissions.modules'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name|regex:/^[a-z][a-z0-9_]*$/',
            'display_name_en' => 'required|string|max:255',
            'display_name_ar' => 'required|string|max:255',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string',
        ]);

        // Validate permissions against config registry
        $allPermissions = Role::getAllPermissions();
        $data['permissions'] = array_values(array_intersect($data['permissions'], $allPermissions));

        if (empty($data['permissions'])) {
            return redirect()->back()->withErrors(['permissions' => 'Please select at least one valid permission.'])->withInput();
        }

        $role = Role::create($data);

        AuditLogger::log('created', $role);

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): Response
    {
        return Inertia::render('Admin/Roles/Edit', [
            'role' => $role,
            'availablePermissions' => config('permissions.modules'),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return redirect()->back()->with('error', 'System roles cannot be modified.');
        }

        $data = $request->validate([
            'display_name_en' => 'required|string|max:255',
            'display_name_ar' => 'required|string|max:255',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string',
        ]);

        // Validate permissions against config registry
        $allPermissions = Role::getAllPermissions();
        $data['permissions'] = array_values(array_intersect($data['permissions'], $allPermissions));

        if (empty($data['permissions'])) {
            return redirect()->back()->withErrors(['permissions' => 'Please select at least one valid permission.'])->withInput();
        }

        $role->update($data);

        AuditLogger::log('updated', $role);

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return redirect()->back()->with('error', 'System roles cannot be deleted.');
        }

        if ($role->users()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete a role that has users assigned to it. Please reassign the users first.');
        }

        AuditLogger::log('deleted', $role);
        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.');
    }
}
