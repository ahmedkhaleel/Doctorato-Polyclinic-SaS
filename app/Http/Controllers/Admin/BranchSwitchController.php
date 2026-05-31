<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sets the staff member's active branch in the session. "all" is allowed only
 * for users who may see all branches (super_admin). Rejects unauthorised branches.
 */
class BranchSwitchController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $data = $request->validate(['branch' => 'required|string']);
        $user = $request->user();
        $key = config('branches.session_key', 'current_branch_id');

        if ($data['branch'] === 'all') {
            if (! $user->canSwitchAllBranches()) {
                return back()->with('error', __('Not authorized for all branches.'));
            }
            $request->session()->put($key, 'all');

            return back()->with('success', __('Viewing all branches.'));
        }

        $branchId = (int) $data['branch'];
        if (! $user->belongsToBranch($branchId)) {
            return back()->with('error', __('You are not assigned to that branch.'));
        }

        $request->session()->put($key, $branchId);

        return back()->with('success', __('Branch switched.'));
    }
}
