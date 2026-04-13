<?php

namespace App\Http\Controllers\Doctor;

use App\Models\DoctorPatientNote;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DoctorPatientNoteController extends BaseDoctorController
{
    /**
     * Store a new quick note on a patient.
     */
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $doctorId = $this->doctorId($request);

        // Verify this patient belongs to this doctor
        $hasVisits = $patient->visits()->where('doctor_id', $doctorId)->exists();
        if (! $hasVisits) {
            abort(403, 'This patient is not in your records.');
        }

        $data = $request->validate([
            'note' => 'required|string|max:5000',
            'is_pinned' => 'nullable|boolean',
        ]);

        DoctorPatientNote::create([
            'doctor_id' => $doctorId,
            'patient_id' => $patient->id,
            'note' => $data['note'],
            'is_pinned' => $data['is_pinned'] ?? false,
        ]);

        return redirect()->back()->with('success', $this->msg('Note added.', 'تمت إضافة الملاحظة.'));
    }

    /**
     * Delete a quick note.
     */
    public function destroy(Request $request, Patient $patient, DoctorPatientNote $note): RedirectResponse
    {
        $doctorId = $this->doctorId($request);

        // Ensure the note belongs to this doctor and patient
        if ((int) $note->doctor_id !== $doctorId || (int) $note->patient_id !== (int) $patient->id) {
            abort(403, 'This note does not belong to you.');
        }

        $note->delete();

        return redirect()->back()->with('success', $this->msg('Note deleted.', 'تم حذف الملاحظة.'));
    }

    /**
     * Toggle the pinned status of a note.
     */
    public function togglePin(Request $request, Patient $patient, DoctorPatientNote $note): RedirectResponse
    {
        $doctorId = $this->doctorId($request);

        if ((int) $note->doctor_id !== $doctorId || (int) $note->patient_id !== (int) $patient->id) {
            abort(403, 'This note does not belong to you.');
        }

        $note->update(['is_pinned' => ! $note->is_pinned]);

        return redirect()->back()->with('success', $this->msg(
            $note->is_pinned ? 'Note pinned.' : 'Note unpinned.',
            $note->is_pinned ? 'تم تثبيت الملاحظة.' : 'تم إلغاء تثبيت الملاحظة.'
        ));
    }
}
