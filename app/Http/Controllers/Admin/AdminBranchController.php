<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Branch registry CRUD (B5). Permission-gated by settings.update.
 */
class AdminBranchController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Branches/Index', [
            'branches' => Branch::withCount('users')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateBranch($request);
        Branch::create($data + ['is_default' => false]);

        return back()->with('success', __('Branch created.'));
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $branch->update($this->validateBranch($request, $branch));

        return back()->with('success', __('Branch updated.'));
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        if ($branch->is_default) {
            return back()->with('error', __('The default branch cannot be deleted.'));
        }
        $branch->update(['is_active' => false]); // soft disable, never hard-delete (FKs)

        return back()->with('success', __('Branch deactivated.'));
    }

    private function validateBranch(Request $request, ?Branch $branch = null): array
    {
        return $request->validate([
            'name_ar' => 'required|string|max:120',
            'name_en' => 'required|string|max:120',
            'code' => 'required|string|max:20|unique:branches,code'.($branch ? ','.$branch->id : ''),
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:60',
            'is_active' => 'sometimes|boolean',
        ]);
    }
}
