<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\InsurancePreAuthorization;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Insurance pre-authorization workflow: a clinic requests the insurer's
 * approval for a planned procedure (with ICD/CPT + estimated cost) BEFORE
 * performing it. Lifecycle: pending → approved/partially_approved/rejected,
 * and auto-expired past valid_until. An approved pre-auth can then be
 * attached to the resulting claim.
 */
class InsurancePreAuthorizationController extends Controller
{
    public function index(Request $request): Response
    {
        $query = InsurancePreAuthorization::with([
            'patient:id,full_name,file_number,phone',
            'doctor:id,name_ar,name_en',
            'patientInsurance.company:id,name_ar,name_en',
        ]);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('auth_number', 'like', "%{$s}%")
                    ->orWhere('procedure_description', 'like', "%{$s}%")
                    ->orWhereHas('patient', fn ($p) => $p->where('full_name', 'like', "%{$s}%")->orWhere('file_number', 'like', "%{$s}%"));
            });
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return Inertia::render('Admin/Insurance/PreAuthorizations/Index', [
            'preAuths' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'status']),
            'stats' => [
                'pending' => InsurancePreAuthorization::where('status', 'pending')->count(),
                'approved' => InsurancePreAuthorization::whereIn('status', ['approved', 'partially_approved'])->count(),
                'expired' => InsurancePreAuthorization::where('status', 'expired')->count(),
                'approved_amount' => (float) InsurancePreAuthorization::whereIn('status', ['approved', 'partially_approved'])->sum('approved_amount'),
            ],
            'patients' => Patient::orderBy('full_name')->limit(500)->get(['id', 'full_name', 'phone', 'file_number']),
            'insurances' => PatientInsurance::with('company:id,name_ar,name_en')
                ->active()  // PatientInsurance has no `status` column — it's is_active
                ->get(['id', 'patient_id', 'insurance_company_id', 'policy_number']),
            'doctors' => Doctor::active()->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'patient_insurance_id' => 'required|exists:patient_insurances,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'procedure_description' => 'required|string|max:2000',
            'icd_code' => 'nullable|string|max:20',
            'cpt_code' => 'nullable|string|max:20',
            'estimated_cost' => 'required|numeric|min:0',
        ]);

        $preAuth = InsurancePreAuthorization::create($data + [
            'auth_number' => InsurancePreAuthorization::generateAuthNumber(),
            'status' => 'pending',
            'requested_by' => auth()->id(),
        ]);

        AuditLogger::log('created', $preAuth);

        return back()->with('success', 'تم إنشاء طلب الموافقة المسبقة.');
    }

    public function updateStatus(Request $request, InsurancePreAuthorization $preAuthorization): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:pending,approved,partially_approved,rejected,expired',
            'approved_amount' => 'nullable|numeric|min:0',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'conditions' => 'nullable|string|max:2000',
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        $update = ['status' => $data['status']];

        if (in_array($data['status'], ['approved', 'partially_approved'], true)) {
            $update['approved_amount'] = $data['approved_amount'] ?? $preAuthorization->estimated_cost;
            $update['valid_from'] = $data['valid_from'] ?? now()->toDateString();
            $update['valid_until'] = $data['valid_until'] ?? now()->addDays(30)->toDateString();
            $update['conditions'] = $data['conditions'] ?? null;
            $update['rejection_reason'] = null;
        } elseif ($data['status'] === 'rejected') {
            $update['rejection_reason'] = $data['rejection_reason'] ?? null;
            $update['approved_amount'] = null;
        }

        $preAuthorization->update($update);
        AuditLogger::log('status_changed', $preAuthorization, ['new_status' => $data['status']]);

        return back()->with('success', 'تم تحديث حالة الموافقة المسبقة.');
    }

    public function destroy(InsurancePreAuthorization $preAuthorization): RedirectResponse
    {
        AuditLogger::log('deleted', $preAuthorization);
        $preAuthorization->delete();

        return back()->with('success', 'تم حذف طلب الموافقة المسبقة.');
    }
}
