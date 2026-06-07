<?php

namespace App\Http\Controllers\Secretary;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PhysioTreatmentPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Front-desk view for physiotherapy. STRICTLY ADMINISTRATIVE: upcoming
 * appointments, the patient roster with outstanding balance, and active plans
 * shown as a remaining-session COUNT for scheduling — never SOAP notes, pain
 * scores, assessments or diagnoses (those stay in the doctor portal).
 */
class SecretaryPhysioController extends BaseSecretaryController
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $today = now()->toDateString();

        $appointments = Booking::query()
            ->where('module', 'physiotherapy')
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('preferred_date', '>=', $today)
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('full_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")))
            ->with(['patient:id,full_name,phone,file_number', 'doctor:id,name_ar,name_en'])
            ->orderBy('preferred_date')->orderBy('preferred_time')
            ->limit(100)->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'booking_number' => $b->booking_number,
                'patient' => $b->patient
                    ? ['id' => $b->patient->id, 'full_name' => $b->patient->full_name, 'phone' => $b->patient->phone, 'file_number' => $b->patient->file_number]
                    : ['full_name' => $b->full_name, 'phone' => $b->phone],
                'doctor' => $b->doctor ? ['name_ar' => $b->doctor->name_ar, 'name_en' => $b->doctor->name_en] : null,
                'date' => optional($b->preferred_date)->toDateString(),
                'time' => $b->preferred_time,
                'status' => $b->status,
            ]);

        $patientIds = Booking::where('module', 'physiotherapy')->whereNotNull('patient_id')->distinct()->pluck('patient_id');
        $roster = Patient::whereIn('id', $patientIds)
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('full_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('file_number', 'like', "%{$search}%")))
            ->orderBy('full_name')->limit(60)
            ->get(['id', 'full_name', 'phone', 'file_number'])
            ->map(fn (Patient $p) => [
                'id' => $p->id,
                'full_name' => $p->full_name,
                'phone' => $p->phone,
                'file_number' => $p->file_number,
                'outstanding' => round(max(0, (float) Invoice::where('patient_id', $p->id)
                    ->where('module', 'physiotherapy')->where('status', '!=', 'paid')
                    ->sum(DB::raw('total - paid_amount'))), 2),
            ]);

        // Active plans → remaining-session count only (scheduling info, no clinical detail).
        $plans = PhysioTreatmentPlan::whereIn('status', ['planned', 'in_progress'])
            ->with('patient:id,full_name,phone')
            ->latest('start_date')->limit(50)->get()
            ->map(fn (PhysioTreatmentPlan $p) => [
                'id' => $p->id,
                'patient' => $p->patient ? ['full_name' => $p->patient->full_name, 'phone' => $p->patient->phone] : null,
                'estimated_sessions' => (int) $p->estimated_sessions,
                'completed_sessions' => (int) $p->completed_sessions,
                'sessions_remaining' => $p->sessions_remaining,
                'frequency' => $p->frequency,
            ]);

        return Inertia::render('Secretary/Physiotherapy/Index', [
            'appointments' => $appointments,
            'roster' => $roster,
            'plans' => $plans,
            'filters' => ['search' => $search],
        ]);
    }
}
