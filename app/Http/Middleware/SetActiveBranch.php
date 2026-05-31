<?php

namespace App\Http\Middleware;

use App\Services\Branch\BranchContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active branch for an authenticated staff request and pins it on
 * the BranchContext. Honours the session branch (validated against the user's
 * assignments) or "all" for super-admins; otherwise the user's primary branch.
 */
class SetActiveBranch
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user) {
            $ctx = app(BranchContext::class);
            $key = config('branches.session_key', 'current_branch_id');
            $session = $request->hasSession() ? $request->session()->get($key) : null;

            if ($session === 'all' && $user->canSwitchAllBranches()) {
                $ctx->setAllBranches();
            } elseif ($session !== null && $user->belongsToBranch((int) $session)) {
                $ctx->set((int) $session);
            } else {
                $ctx->set($user->primaryBranchId());
            }
        }

        return $next($request);
    }
}
