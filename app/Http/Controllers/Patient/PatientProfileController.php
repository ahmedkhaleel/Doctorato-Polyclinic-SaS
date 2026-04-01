<?php

namespace App\Http\Controllers\Patient;

use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class PatientProfileController extends BasePatientController
{
    public function index(Request $request): Response
    {
        $patient = $this->patient($request);

        return Inertia::render('Patient/Profile/Index', [
            'patient' => $patient->only([
                'id', 'full_name', 'phone', 'phone2', 'email',
                'date_of_birth', 'gender', 'blood_type', 'marital_status',
                'nationality', 'address', 'occupation',
                'emergency_contact_name', 'emergency_contact_phone',
                'file_number',
            ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $patient = $this->patient($request);

        $data = $request->validate([
            'email' => ['nullable', 'email', 'max:255', Rule::unique('patients', 'email')->ignore($patient->id)],
            'phone2' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
        ]);

        // Sync email to users table to keep auth consistent
        DB::transaction(function () use ($patient, $data) {
            $patient->update($data);

            if (isset($data['email']) && $patient->user && $patient->user->email !== $data['email']) {
                $patient->user->update(['email' => $data['email']]);
            }
        });

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
