<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(): Response
    {
        $departments = Department::withCount('employees')
            ->with('manager:id,name')
            ->ordered()
            ->get();

        $managers = User::active()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Departments/Index', [
            'departments' => $departments,
            'managers' => $managers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'manager_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $department = Department::create($data);

        AuditLogger::log('created', $department);

        return redirect()->back()->with('success', 'Department created successfully.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'manager_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $department->update($data);

        AuditLogger::log('updated', $department);

        return redirect()->back()->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->employees()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete department with existing employees.');
        }

        AuditLogger::log('deleted', $department);

        $department->delete();

        return redirect()->back()->with('success', 'Department deleted successfully.');
    }
}
