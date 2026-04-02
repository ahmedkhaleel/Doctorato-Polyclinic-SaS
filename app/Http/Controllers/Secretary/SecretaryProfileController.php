<?php

namespace App\Http\Controllers\Secretary;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SecretaryProfileController extends BaseSecretaryController
{
    public function index(Request $request): Response
    {
        return Inertia::render('Secretary/Profile/Index', [
            'user' => $request->user()->only(['id', 'name', 'email', 'created_at']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $request->user()->id,
        ]);

        $request->user()->update($data);

        return redirect()->back()->with('success', $this->msg('Profile updated successfully.', 'تم تحديث الملف الشخصي بنجاح.'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return redirect()->back()->with('success', $this->msg('Password updated successfully.', 'تم تحديث كلمة المرور بنجاح.'));
    }
}
