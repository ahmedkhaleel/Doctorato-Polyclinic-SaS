<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Multi-branch master switch
    |--------------------------------------------------------------------------
    | While false, the branch Global Scope is a no-op even where the trait is
    | applied — so behaviour is identical to single-clinic. Flip to true only
    | once the foundation + backfill are verified on production.
    */
    'enabled' => (bool) env('BRANCHES_ENABLED', false),

    /*
    | The fallback / default branch every existing row is backfilled to and the
    | context resolves to when there is no session override (id of "Main Branch").
    */
    'default_id' => (int) env('BRANCHES_DEFAULT_ID', 1),

    /*
    | Session key holding the staff member's currently-active branch.
    */
    'session_key' => 'current_branch_id',
];
