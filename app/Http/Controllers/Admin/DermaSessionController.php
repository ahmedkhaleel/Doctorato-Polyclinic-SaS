<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DermaSession;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\CosmeticDermaInvoiceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DermaSessionController extends Controller
{
    public function __construct(private CosmeticDermaInvoiceService $invoicing) {}

    public function index(Request $request)
    {
        $query = DermaSession::with(['patient:id,full_name,phone', 'doctor:id,name_ar,name_en']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('patient', fn($q) => $q->where('full_name', 'like', "%$s%"));
        }
        if ($request->filled('type')) $query->where('session_type', $request->type);
        if ($request->filled('patient_id')) $query->where('patient_id', $request->patient_id);

        $sessions = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Admin/Derma/Sessions/Index', [
            'sessions' => $sessions,
            'filters' => $request->only(['search', 'type', 'patient_id']),
            'types' => DermaSession::TYPES,
            'patients' => Patient::orderBy('full_name')->limit(500)->get(['id', 'full_name', 'phone']),
            'doctors' => Doctor::orderBy('name_ar')->get(['id', 'name_ar', 'name_en']),
        ]);
    }

    public function store(Request $request)
    {
        $session = DermaSession::create($this->validated($request));
        $this->invoicing->generateForDermaSession($session);
        $this->syncPlan($session);
        return back()->with('success', 'تم إضافة الجلسة');
    }

    public function update(Request $request, DermaSession $session)
    {
        $original = $session->treatment_plan_id;
        $session->update($this->validated($request));
        $fresh = $session->fresh();

        if ($fresh->completed_at === null) {
            // Un-completed → void any invoice line it had produced.
            $this->invoicing->reverseBilling($fresh);
        } else {
            // Bill on completion (idempotent — never invoices the same session twice).
            $this->invoicing->generateForDermaSession($fresh);
        }
        $this->syncPlan($fresh);
        // If the session was moved off a plan, refresh the old plan too.
        if ($original && $original !== $fresh->treatment_plan_id) {
            optional(\App\Models\DermaTreatmentPlan::find($original))->syncProgress();
        }
        return back()->with('success', 'تم التحديث');
    }

    /** Recompute a linked course's progress after a session change. */
    private function syncPlan(DermaSession $session): void
    {
        if ($session->treatment_plan_id) {
            optional($session->treatmentPlan)->syncProgress();
        }
    }

    public function destroy(DermaSession $session)
    {
        $plan = $session->treatmentPlan;
        $this->invoicing->reverseBilling($session); // void its invoice line first
        $session->delete();
        optional($plan)->syncProgress();
        return back()->with('success', 'تم الحذف');
    }

    private function validated(Request $r): array
    {
        return $r->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'visit_id' => 'nullable|exists:visits,id',
            'treatment_plan_id' => 'nullable|exists:derma_treatment_plans,id',
            'session_type' => 'required|in:' . implode(',', DermaSession::TYPES),
            'area_treated' => 'nullable|string|max:255',
            'product_used' => 'nullable|string|max:255',
            'settings_json' => 'nullable|array',
            'session_number' => 'nullable|integer|min:1',
            'total_sessions' => 'nullable|integer|min:1',
            'cost' => 'nullable|numeric|min:0',
            'completed_at' => 'nullable|date',
            'next_session_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
    }
}
