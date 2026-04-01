<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class PrescriptionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Prescription::with(['patient', 'doctor', 'visit']);

        if ($module = $request->input('module')) {
            $query->whereHas('visit', fn ($q) => $q->where('module', $module));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('diagnosis', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($pq) use ($search) {
                        $pq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('doctor', function ($dq) use ($search) {
                        $dq->where('name_ar', 'like', "%{$search}%")
                            ->orWhere('name_en', 'like', "%{$search}%");
                    });
            });
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $prescriptions = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Prescriptions/Index', [
            'prescriptions' => $prescriptions,
            'filters' => $request->only(['search', 'date_from', 'date_to', 'module']),
            'doctors' => Doctor::select('id', 'name_en', 'name_ar')->where('status', 'active')->orderBy('name_en')->get(),
            'patients' => Patient::select('id', 'full_name', 'phone', 'file_number')->orderBy('full_name')->get(),
        ]);
    }

    public function show(Prescription $prescription): Response
    {
        $prescription->load(['items', 'patient', 'doctor', 'visit']);

        return Inertia::render('Admin/Prescriptions/Show', [
            'prescription' => $prescription,
        ]);
    }

    public function print(Prescription $prescription): Response
    {
        $prescription->load(['items', 'patient', 'doctor', 'visit']);

        return Inertia::render('Admin/Prescriptions/Print', [
            'prescription' => $prescription,
        ])->withViewData(['layout' => 'none']);
    }

    public function downloadPdf(Prescription $prescription): HttpResponse
    {
        $prescription->load(['items', 'patient', 'doctor', 'visit']);

        $pdf = Pdf::loadView('pdf.prescription', compact('prescription'))
            ->setPaper('a4', 'portrait');

        $filename = 'prescription-' . ($prescription->patient->full_name ?? 'patient') . '-' . $prescription->created_at->format('Y-m-d') . '.pdf';
        $filename = preg_replace('/[^A-Za-z0-9\-\.]/', '_', $filename);

        return $pdf->download($filename);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'visit_id' => 'nullable|exists:visits,id',
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'diagnosis' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.medication_name' => 'required|string|max:255',
            'items.*.dosage' => 'nullable|string|max:255',
            'items.*.frequency' => 'nullable|string|max:255',
            'items.*.duration' => 'nullable|string|max:255',
            'items.*.instructions' => 'nullable|string',
        ]);

        DB::transaction(function () use ($data) {
            $prescription = Prescription::create([
                'visit_id' => $data['visit_id'] ?? null,
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'],
                'diagnosis' => $data['diagnosis'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $index => $item) {
                PrescriptionItem::create([
                    'prescription_id' => $prescription->id,
                    'medication_name' => $item['medication_name'],
                    'dosage' => $item['dosage'] ?? null,
                    'frequency' => $item['frequency'] ?? null,
                    'duration' => $item['duration'] ?? null,
                    'instructions' => $item['instructions'] ?? null,
                    'sort_order' => $index,
                ]);
            }

            AuditLogger::log('created', $prescription);
        });

        return redirect()->back()->with('success', 'Prescription created successfully.');
    }

    public function update(Request $request, Prescription $prescription): RedirectResponse
    {
        $data = $request->validate([
            'diagnosis' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.medication_name' => 'required|string|max:255',
            'items.*.dosage' => 'nullable|string|max:255',
            'items.*.frequency' => 'nullable|string|max:255',
            'items.*.duration' => 'nullable|string|max:255',
            'items.*.instructions' => 'nullable|string',
        ]);

        DB::transaction(function () use ($data, $prescription) {
            $prescription->update([
                'diagnosis' => $data['diagnosis'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Delete existing items and recreate
            $prescription->items()->delete();

            foreach ($data['items'] as $index => $item) {
                PrescriptionItem::create([
                    'prescription_id' => $prescription->id,
                    'medication_name' => $item['medication_name'],
                    'dosage' => $item['dosage'] ?? null,
                    'frequency' => $item['frequency'] ?? null,
                    'duration' => $item['duration'] ?? null,
                    'instructions' => $item['instructions'] ?? null,
                    'sort_order' => $index,
                ]);
            }

            AuditLogger::log('updated', $prescription);
        });

        return redirect()->back()->with('success', 'Prescription updated successfully.');
    }

    public function destroy(Prescription $prescription): RedirectResponse
    {
        AuditLogger::log('deleted', $prescription);

        $prescription->items()->delete();
        $prescription->delete();

        return redirect()->back()->with('success', 'Prescription deleted successfully.');
    }
}
