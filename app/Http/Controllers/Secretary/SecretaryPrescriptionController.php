<?php

namespace App\Http\Controllers\Secretary;

use App\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class SecretaryPrescriptionController extends BaseSecretaryController
{
    public function index(Request $request): Response
    {
        $query = Prescription::with(['patient', 'doctor', 'items']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', function ($pq) use ($search) {
                    $pq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })->orWhereHas('doctor', function ($dq) use ($search) {
                    $dq->where('name_en', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%");
                });
            });
        }

        $prescriptions = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Secretary/Prescriptions/Index', [
            'prescriptions' => $prescriptions,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(Prescription $prescription): Response
    {
        $prescription->load(['items', 'patient', 'doctor', 'visit']);

        return Inertia::render('Secretary/Prescriptions/Show', [
            'prescription' => $prescription,
        ]);
    }

    public function print(Prescription $prescription): Response
    {
        $prescription->load(['items', 'patient', 'doctor', 'visit']);

        return Inertia::render('Secretary/Prescriptions/Print', [
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
}
