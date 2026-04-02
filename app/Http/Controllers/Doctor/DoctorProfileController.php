<?php

namespace App\Http\Controllers\Doctor;

use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class DoctorProfileController extends BaseDoctorController
{
    public function index(Request $request): Response
    {
        $doctor = $this->doctor($request);
        $doctor->load(['schedules', 'serviceRates.service:id,name_en,name_ar']);

        return Inertia::render('Doctor/Profile/Index', [
            'doctor' => $doctor,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $doctor = $this->doctor($request);

        $request->validate([
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'bio_en' => 'nullable|string|max:2000',
            'bio_ar' => 'nullable|string|max:2000',
            'clinic_notes' => 'nullable|string|max:2000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $updateData = $request->only(['phone', 'email', 'bio_en', 'bio_ar', 'clinic_notes']);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($doctor->photo && Storage::disk('public')->exists($doctor->photo)) {
                Storage::disk('public')->delete($doctor->photo);
            }
            $updateData['photo'] = $request->file('photo')->store('doctors', 'public');
        }

        $doctor->update($updateData);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        AuditLogger::log('password_changed', $user);

        return redirect()->back()->with('success', 'Password changed successfully.');
    }
}
