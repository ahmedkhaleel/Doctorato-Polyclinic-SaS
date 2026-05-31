<?php

namespace App\Http\Controllers\Patient;

use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
            'patient' => array_merge(
                $patient->only([
                    'id', 'full_name', 'phone', 'phone2', 'email',
                    'date_of_birth', 'gender', 'blood_type', 'marital_status',
                    'nationality', 'address', 'occupation',
                    'emergency_contact_name', 'emergency_contact_phone',
                    'file_number',
                    // Notification preferences
                    'preferred_language',
                    'notify_email_bookings', 'notify_email_reminders', 'notify_email_marketing',
                    'notify_sms_bookings', 'notify_sms_reminders', 'notify_sms_marketing',
                    'notify_whatsapp_bookings', 'notify_whatsapp_reminders', 'notify_whatsapp_marketing',
                ]),
                ['photo_url' => $patient->photo_url]
            ),
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

    /**
     * Upload or replace the patient's profile photo. Stored on the
     * `public` disk under patient-photos/, max 4MB, image/* only.
     * The previous photo (if any) is deleted to avoid orphan files.
     *
     * Note: file_number, user_id, is_active are intentionally excluded
     * from $fillable — `photo` is included though, so a regular update()
     * works without forceFill().
     */
    public function uploadPhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $patient = $this->patient($request);
        $oldPath = $patient->photo;

        $path = $request->file('photo')->store('patient-photos', 'public');

        $patient->update(['photo' => $path]);

        // Best-effort cleanup of the previous file.
        if ($oldPath) {
            try {
                Storage::disk('public')->delete($oldPath);
            } catch (\Throwable $e) { /* swallow — log fallback below */
            }
        }

        AuditLogger::log('patient_photo_uploaded', $patient, ['path' => $path]);

        return back()->with('success', 'Profile photo updated.');
    }

    public function deletePhoto(Request $request): RedirectResponse
    {
        $patient = $this->patient($request);
        $oldPath = $patient->photo;

        if (! $oldPath) {
            return back()->with('error', 'No photo to delete.');
        }

        $patient->update(['photo' => null]);
        try {
            Storage::disk('public')->delete($oldPath);
        } catch (\Throwable $e) {
        }

        AuditLogger::log('patient_photo_deleted', $patient);

        return back()->with('success', 'Profile photo removed.');
    }

    /**
     * Save the patient's notification preferences. All flags are
     * boolean — checkbox-driven UI on the profile page.
     */
    public function updatePreferences(Request $request): RedirectResponse
    {
        $patient = $this->patient($request);

        $data = $request->validate([
            'preferred_language' => 'nullable|in:ar,en',
            'notify_email_bookings' => 'sometimes|boolean',
            'notify_email_reminders' => 'sometimes|boolean',
            'notify_email_marketing' => 'sometimes|boolean',
            'notify_sms_bookings' => 'sometimes|boolean',
            'notify_sms_reminders' => 'sometimes|boolean',
            'notify_sms_marketing' => 'sometimes|boolean',
            'notify_whatsapp_bookings' => 'sometimes|boolean',
            'notify_whatsapp_reminders' => 'sometimes|boolean',
            'notify_whatsapp_marketing' => 'sometimes|boolean',
        ]);

        // Coerce missing checkboxes (browsers omit unchecked ones) to false.
        foreach ([
            'notify_email_bookings', 'notify_email_reminders', 'notify_email_marketing',
            'notify_sms_bookings', 'notify_sms_reminders', 'notify_sms_marketing',
            'notify_whatsapp_bookings', 'notify_whatsapp_reminders', 'notify_whatsapp_marketing',
        ] as $flag) {
            if (! array_key_exists($flag, $data)) {
                $data[$flag] = false;
            }
        }

        \App\Services\Notifications\ConsentService::sync($patient, $data, 'patient_portal', $request->ip());
        $patient->update($data);
        AuditLogger::log('notification_preferences_updated', $patient);

        return redirect()->back()->with('success', 'Notification preferences saved.');
    }
}
