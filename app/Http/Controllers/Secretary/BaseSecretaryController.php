<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

abstract class BaseSecretaryController extends Controller
{
    /**
     * Get the authenticated secretary user.
     */
    protected function user(Request $request): User
    {
        return $request->user();
    }
}
