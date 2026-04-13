<?php

namespace App\Http\Controllers\Doctor;

use App\Models\DoctorFavoritePatient;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorFavoritePatientController extends BaseDoctorController
{
    /**
     * List the doctor's favorite patients.
     */
    public function index(Request $request): Response
    {
        $doctorId = $this->doctorId($request);

        $favorites = Patient::whereHas('doctorFavorites', function ($q) use ($doctorId) {
            $q->where('doctor_id', $doctorId);
        })
            ->withCount(['visits' => function ($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId);
            }])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Doctor/Patients/Favorites', [
            'patients' => $favorites,
        ]);
    }

    /**
     * Toggle a patient as favorite.
     */
    public function toggle(Request $request, Patient $patient): RedirectResponse
    {
        $doctorId = $this->doctorId($request);

        // Verify this patient belongs to this doctor
        $hasVisits = $patient->visits()->where('doctor_id', $doctorId)->exists();
        if (! $hasVisits) {
            abort(403, 'This patient is not in your records.');
        }

        $existing = DoctorFavoritePatient::where('doctor_id', $doctorId)
            ->where('patient_id', $patient->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $message = $this->msg('Patient removed from favorites.', 'تمت إزالة المريض من المفضلة.');
        } else {
            DoctorFavoritePatient::create([
                'doctor_id' => $doctorId,
                'patient_id' => $patient->id,
            ]);
            $message = $this->msg('Patient added to favorites.', 'تمت إضافة المريض إلى المفضلة.');
        }

        return redirect()->back()->with('success', $message);
    }
}
