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

    /**
     * Return a bilingual flash message based on current locale.
     */
    protected function msg(string $en, string $ar): string
    {
        return app()->getLocale() === 'ar' ? $ar : $en;
    }
}
