<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Doctor;
use App\Models\User;
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
        $locale = app()->getLocale();

        $staff = User::with(['role:id,name,display_name_ar,display_name_en', 'branches:id'])
            ->orderBy('name')->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'role' => $u->role ? ($locale === 'ar' ? $u->role->display_name_ar : $u->role->display_name_en) : null,
                'branch_ids' => $u->branches->pluck('id')->all(),
            ]);

        $doctors = Doctor::with('branches:id')->orderBy('name_en')->get()
            ->map(fn (Doctor $d) => [
                'id' => $d->id,
                'name' => $locale === 'ar' ? $d->name_ar : $d->name_en,
                'branch_ids' => $d->branches->pluck('id')->all(),
            ]);

        return Inertia::render('Admin/Branches/Index', [
            'branches' => Branch::withCount(['users', 'doctors'])->orderBy('id')->get(),
            'staff' => $staff,
            'doctors' => $doctors,
        ]);
    }

    /**
     * Sync the users and doctors assigned to a branch. Diff-based so the
     * is_primary pivot on untouched branch_user rows is preserved.
     */
    public function syncMembers(Request $request, Branch $branch): RedirectResponse
    {
        $data = $request->validate([
            'user_ids' => 'array',
            'user_ids.*' => 'integer|exists:users,id',
            'doctor_ids' => 'array',
            'doctor_ids.*' => 'integer|exists:doctors,id',
        ]);

        $this->syncPivot($branch->users(), $data['user_ids'] ?? []);
        $this->syncPivot($branch->doctors(), $data['doctor_ids'] ?? []);

        return back()->with('success', __('Branch assignments updated.'));
    }

    private function syncPivot($relation, array $ids): void
    {
        $existing = $relation->pluck($relation->getRelated()->getTable().'.id')->all();
        $relation->attach(array_values(array_diff($ids, $existing)));
        $relation->detach(array_values(array_diff($existing, $ids)));
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
