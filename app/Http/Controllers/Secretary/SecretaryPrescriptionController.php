<?php

namespace App\Http\Controllers\Secretary;

use App\Models\Prescription;
use Illuminate\Http\Request;
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
}
