<?php

namespace App\Http\Controllers\Secretary;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\NeuropsychEncounter;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * P1-1 — front-desk view for the psychiatry & neurology modules. STRICTLY
 * ADMINISTRATIVE: upcoming appointments, a patient roster, and billing status.
 * The reception desk NEVER sees clinical content (encounters, notes, risk
 * assessments, controlled prescriptions) — that stays behind the doctor portal
 * and the {module}.view_sensitive permission. One controller serves both
 * modules; the module comes from the route default `npModule`.
 */
class SecretaryNeuropsychController extends BaseSecretaryController
{
    private function module(Request $request): string
    {
        $m = (string) $request->route('npModule');

        return in_array($m, NeuropsychEncounter::MODULES, true) ? $m : 'psychiatry';
    }

    public function index(Request $request): Response
    {
        $module = $this->module($request);
        $search = trim((string) $request->input('search', ''));
        $today = now()->toDateString();

        // Upcoming appointments (bookings) for this module — front-desk queue.
        $appointments = Booking::query()
            ->where('module', $module)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('preferred_date', '>=', $today)
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('full_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")))
            ->with(['patient:id,full_name,phone,file_number', 'doctor:id,name_ar,name_en'])
            ->orderBy('preferred_date')
            ->orderBy('preferred_time')
            ->limit(100)
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'booking_number' => $b->booking_number,
                'patient' => $b->patient ? [
                    'id' => $b->patient->id,
                    'full_name' => $b->patient->full_name,
                    'phone' => $b->patient->phone,
                    'file_number' => $b->patient->file_number,
                ] : ['full_name' => $b->full_name, 'phone' => $b->phone],
                'doctor' => $b->doctor ? ['name_ar' => $b->doctor->name_ar, 'name_en' => $b->doctor->name_en] : null,
                'date' => optional($b->preferred_date)->toDateString(),
                'time' => $b->preferred_time,
                'status' => $b->status,
            ]);

        // Patient roster — distinct patients who have a booking in this module.
        $patientIds = Booking::where('module', $module)
            ->whereNotNull('patient_id')
            ->distinct()
            ->pluck('patient_id');

        $roster = Patient::whereIn('id', $patientIds)
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('full_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('file_number', 'like', "%{$search}%")))
            ->orderBy('full_name')
            ->limit(60)
            ->get(['id', 'full_name', 'phone', 'file_number'])
            ->map(function (Patient $p) use ($module) {
                // Outstanding balance on this module's invoices (admin info only).
                $outstanding = (float) Invoice::where('patient_id', $p->id)
                    ->where('module', $module)
                    ->where('status', '!=', 'paid')
                    ->sum(\Illuminate\Support\Facades\DB::raw('total - paid_amount'));

                return [
                    'id' => $p->id,
                    'full_name' => $p->full_name,
                    'phone' => $p->phone,
                    'file_number' => $p->file_number,
                    'outstanding' => round(max(0, $outstanding), 2),
                ];
            });

        $doctors = Doctor::forModule($module)->where('status', 'active')
            ->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);

        return Inertia::render('Secretary/Neuropsych/Index', [
            'module' => $module,
            'appointments' => $appointments,
            'roster' => $roster,
            'doctors' => $doctors,
            'filters' => ['search' => $search],
        ]);
    }
}
