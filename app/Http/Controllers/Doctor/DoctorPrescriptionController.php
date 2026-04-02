<?php

namespace App\Http\Controllers\Doctor;

use App\Models\DentalPrescriptionTemplate;
use App\Models\Medication;
use App\Models\Prescription;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use App\Services\AuditLogger;
use App\Services\DentalPrescriptionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class DoctorPrescriptionController extends BaseDoctorController
{
    public function index(Request $request): Response
    {
        $doctorId = $this->doctorId($request);

        $query = Prescription::with(['patient:id,full_name,file_number,phone,date_of_birth,gender,medical_notes', 'items'])
            ->where('doctor_id', $doctorId);

        if ($search = $request->input('search')) {
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('file_number', 'like', "%{$search}%");
            });
        }

        $prescriptions = $query->latest()->paginate(15)->withQueryString();

        // Common presets for quick selection
        $presets = [
            'frequencies' => [
                'Once daily', 'Twice daily', 'Three times daily', 'Four times daily',
                'Every 8 hours', 'Every 12 hours', 'Once weekly', 'As needed (PRN)',
                'Before meals', 'After meals', 'At bedtime',
            ],
            'durations' => [
                '3 days', '5 days', '7 days', '10 days', '14 days', '21 days',
                '1 month', '2 months', '3 months', '6 months', 'Ongoing',
            ],
            'dosage_forms' => [
                'Tablet', 'Capsule', 'Cream', 'Ointment', 'Gel', 'Lotion',
                'Solution', 'Drops', 'Injection', 'Syrup', 'Spray', 'Patch', 'Shampoo',
            ],
        ];

        return Inertia::render('Doctor/Prescriptions/Index', [
            'prescriptions' => $prescriptions,
            'presets' => $presets,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $doctor = $this->doctor($request);

        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'visit_id' => 'nullable|exists:visits,id',
            'diagnosis' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.medication_name' => 'required|string|max:255',
            'items.*.dosage' => 'nullable|string|max:255',
            'items.*.frequency' => 'nullable|string|max:255',
            'items.*.duration' => 'nullable|string|max:255',
            'items.*.instructions' => 'nullable|string|max:500',
        ]);

        // Verify patient belongs to this doctor (has at least one visit with them)
        $hasPatient = Visit::where('doctor_id', $doctor->id)
            ->where('patient_id', $data['patient_id'])->exists();
        if (!$hasPatient) {
            abort(403, 'This patient is not in your records.');
        }

        // Verify visit belongs to this doctor (if provided)
        if (!empty($data['visit_id'])) {
            $visit = Visit::findOrFail($data['visit_id']);
            if ((int) $visit->doctor_id !== (int) $doctor->id) {
                abort(403, 'This visit does not belong to you.');
            }
        }

        $prescription = Prescription::create([
            'patient_id' => $data['patient_id'],
            'doctor_id' => $doctor->id,
            'visit_id' => $data['visit_id'] ?? null,
            'diagnosis' => $data['diagnosis'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        foreach ($data['items'] as $index => $item) {
            $item['sort_order'] = $index + 1;
            $prescription->items()->create($item);
        }

        return redirect()->back()->with('success', $this->msg('Prescription created successfully.', 'تم إنشاء الوصفة بنجاح.'));
    }

    public function update(Request $request, Prescription $prescription): RedirectResponse
    {
        $this->authorizeDoctor($request, $prescription);

        $data = $request->validate([
            'diagnosis' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.medication_name' => 'required|string|max:255',
            'items.*.dosage' => 'nullable|string|max:255',
            'items.*.frequency' => 'nullable|string|max:255',
            'items.*.duration' => 'nullable|string|max:255',
            'items.*.instructions' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($prescription, $data) {
            $prescription->update([
                'diagnosis' => $data['diagnosis'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $prescription->items()->delete();
            foreach ($data['items'] as $index => $item) {
                $item['sort_order'] = $index + 1;
                $prescription->items()->create($item);
            }
        });

        return redirect()->back()->with('success', $this->msg('Prescription updated successfully.', 'تم تحديث الوصفة بنجاح.'));
    }

    public function destroy(Request $request, Prescription $prescription): RedirectResponse
    {
        $this->authorizeDoctor($request, $prescription);

        AuditLogger::log('deleted', $prescription);

        $prescription->items()->delete();
        $prescription->delete();

        return redirect()->back()->with('success', $this->msg('Prescription deleted successfully.', 'تم حذف الوصفة بنجاح.'));
    }

    public function duplicate(Request $request, Prescription $prescription): RedirectResponse
    {
        $this->authorizeDoctor($request, $prescription);
        $prescription->load('items');

        $new = Prescription::create([
            'patient_id' => $prescription->patient_id,
            'doctor_id'  => $prescription->doctor_id,
            'visit_id'   => null,
            'diagnosis'  => $prescription->diagnosis,
            'notes'      => $prescription->notes,
        ]);

        foreach ($prescription->items as $item) {
            $new->items()->create([
                'medication_name' => $item->medication_name,
                'dosage'          => $item->dosage,
                'frequency'       => $item->frequency,
                'duration'        => $item->duration,
                'instructions'    => $item->instructions,
                'sort_order'      => $item->sort_order,
            ]);
        }

        AuditLogger::log('duplicated', $new, ['duplicated_from' => $prescription->id]);

        return redirect()->back()->with('success', $this->msg('Prescription duplicated successfully.', 'تم نسخ الوصفة بنجاح.'));
    }

    public function downloadPdf(Request $request, Prescription $prescription): HttpResponse
    {
        $this->authorizeDoctor($request, $prescription);

        $prescription->load(['patient', 'doctor', 'items']);

        $pdf = Pdf::loadView('pdf.prescription', ['prescription' => $prescription]);
        $pdf->setPaper('A4');

        $filename = 'Rx-' . ($prescription->patient->file_number ?? $prescription->id) . '-' . $prescription->created_at->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    public function printPdf(Request $request, Prescription $prescription): HttpResponse
    {
        $this->authorizeDoctor($request, $prescription);

        $prescription->load(['patient', 'doctor', 'items']);

        $pdf = Pdf::loadView('pdf.prescription', ['prescription' => $prescription]);
        $pdf->setPaper('A4');

        return $pdf->stream('prescription.pdf');
    }

    /**
     * Search patients by name, phone, or file number (async autocomplete).
     * Only returns patients who have visited this doctor.
     */
    public function searchPatients(Request $request): JsonResponse
    {
        $doctorId = $this->doctorId($request);
        $search = trim($request->input('q', ''));

        if (mb_strlen($search) < 2) {
            return response()->json([]);
        }

        $patients = \App\Models\Patient::where('is_active', true)
            ->whereHas('visits', fn ($q) => $q->where('doctor_id', $doctorId))
            ->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('file_number', 'like', "%{$search}%");
            })
            ->select('id', 'full_name', 'phone', 'file_number', 'date_of_birth', 'gender', 'medical_notes')
            ->limit(15)
            ->get();

        return response()->json($patients);
    }

    public function searchMedications(Request $request): JsonResponse
    {
        $search = $request->input('q', '');

        $medications = Medication::where('name', 'like', "%{$search}%")
            ->orWhere('generic_name', 'like', "%{$search}%")
            ->limit(10)
            ->get();

        return response()->json($medications);
    }

    public function dentalTemplates(Request $request): JsonResponse
    {
        $treatmentType = $request->input('treatment_type');

        $query = DentalPrescriptionTemplate::active()->with('items')->orderBy('sort_order');

        if ($treatmentType) {
            $query->forTreatmentType($treatmentType);
        }

        return response()->json($query->get());
    }

    public function applyDentalTemplate(Request $request, DentalPrescriptionTemplate $template): RedirectResponse
    {
        $doctor = $this->doctor($request);

        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'visit_id' => 'nullable|exists:visits,id',
            'dental_treatment_id' => 'nullable|exists:dental_treatments,id',
        ]);

        // Verify this doctor has treated the patient (ownership check)
        $hasVisit = Visit::where('doctor_id', $doctor->id)
            ->where('patient_id', $data['patient_id'])
            ->exists();
        if (!$hasVisit) {
            abort(403, 'You can only prescribe for your own patients.');
        }

        // If visit_id provided, verify it belongs to this doctor
        if (!empty($data['visit_id'])) {
            $visit = Visit::findOrFail($data['visit_id']);
            if ($visit->doctor_id !== $doctor->id) {
                abort(403, 'This visit does not belong to you.');
            }
        }

        app(DentalPrescriptionService::class)->generateFromTemplate(
            $template,
            $data['patient_id'],
            $doctor->id,
            $data['visit_id'] ?? null,
            $data['dental_treatment_id'] ?? null
        );

        return redirect()->back()->with('success', $this->msg('Prescription created from template.', 'تم إنشاء الوصفة من القالب.'));
    }
}
