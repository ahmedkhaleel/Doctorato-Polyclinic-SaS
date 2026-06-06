<?php

namespace App\Http\Controllers\Secretary;

use App\Models\Booking;
use App\Models\CosmeticPackagePurchase;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * P1-2 — front-desk view for the dermatology & cosmetic module. ADMINISTRATIVE
 * ONLY: upcoming appointments, patient roster with outstanding balance, and
 * active cosmetic package balances (remaining sessions) — front-desk needs the
 * session count to schedule, but never sees clinical photos or treatment notes.
 */
class SecretaryDermaController extends BaseSecretaryController
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $today = now()->toDateString();

        $appointments = Booking::query()
            ->where('module', 'derma')
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

        $patientIds = Booking::where('module', 'derma')->whereNotNull('patient_id')->distinct()->pluck('patient_id');
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
                    ->where('module', 'derma')->where('status', '!=', 'paid')
                    ->sum(DB::raw('total - paid_amount'))), 2),
            ]);

        // Active cosmetic packages with remaining sessions (front-desk scheduling info).
        $packages = CosmeticPackagePurchase::active()
            ->with(['patient:id,full_name,phone', 'package:id,name_ar,name_en'])
            ->whereColumn('sessions_used', '<', 'total_sessions')
            ->latest('purchased_at')->limit(50)->get()
            ->map(fn (CosmeticPackagePurchase $pp) => [
                'id' => $pp->id,
                'patient' => $pp->patient ? ['full_name' => $pp->patient->full_name, 'phone' => $pp->patient->phone] : null,
                'package' => $pp->package ? ['name_ar' => $pp->package->name_ar, 'name_en' => $pp->package->name_en] : null,
                'total_sessions' => (int) $pp->total_sessions,
                'sessions_used' => (int) $pp->sessions_used,
                'sessions_remaining' => $pp->sessions_remaining,
                'expires_at' => optional($pp->expires_at)->toDateString(),
            ]);

        return Inertia::render('Secretary/Derma/Index', [
            'appointments' => $appointments,
            'roster' => $roster,
            'packages' => $packages,
            'filters' => ['search' => $search],
        ]);
    }
}
