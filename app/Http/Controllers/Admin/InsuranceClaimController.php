<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\PatientInsurance;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InsuranceClaimController extends Controller
{
    public function index(Request $request)
    {
        $claims = InsuranceClaim::with([
                'patient:id,full_name,file_number',
                'patientInsurance.company:id,name_ar,name_en,code',
                'invoice:id,invoice_number,total',
            ])
            ->when($request->search, function ($q, $s) {
                $q->where('claim_number', 'like', "%{$s}%")
                  ->orWhereHas('patient', fn ($pq) => $pq->where('full_name', 'like', "%{$s}%")->orWhere('file_number', 'like', "%{$s}%"));
            })
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->company_id, fn ($q, $c) => $q->whereHas('patientInsurance', fn ($pi) => $pi->where('insurance_company_id', $c)))
            ->when($request->date_from, fn ($q, $d) => $q->where('service_date', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->where('service_date', '<=', $d))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        // Dashboard stats
        $stats = Cache::remember('insurance_claims_stats', 300, function () {
            return [
                'pending_count' => InsuranceClaim::pending()->count(),
                'pending_amount' => (float) InsuranceClaim::pending()->sum('covered_amount'),
                'unpaid_amount' => (float) InsuranceClaim::unpaid()->sum(DB::raw('COALESCE(approved_amount, covered_amount) - paid_amount')),
                'this_month_submitted' => InsuranceClaim::where('submitted_at', '>=', now()->startOfMonth())->count(),
                'this_month_paid' => (float) InsuranceClaim::where('paid_at', '>=', now()->startOfMonth())->sum('paid_amount'),
                'rejection_rate' => InsuranceClaim::where('created_at', '>=', now()->subMonths(3))->count() > 0
                    ? round(InsuranceClaim::where('status', 'rejected')->where('created_at', '>=', now()->subMonths(3))->count() / InsuranceClaim::where('created_at', '>=', now()->subMonths(3))->count() * 100, 1)
                    : 0,
            ];
        });

        $companies = InsuranceCompany::active()->select('id', 'name_ar', 'name_en', 'code')->get();

        return Inertia::render('Admin/Insurance/Claims/Index', [
            'claims' => $claims,
            'stats' => $stats,
            'companies' => $companies,
            'filters' => $request->only(['search', 'status', 'company_id', 'date_from', 'date_to']),
        ]);
    }

    public function show(InsuranceClaim $claim)
    {
        $claim->load([
            'patientInsurance.company',
            'patientInsurance.plan',
            'patient',
            'invoice',
            'visit',
            'creator:id,name',
        ]);

        return Inertia::render('Admin/Insurance/Claims/Show', [
            'claim' => $claim,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_insurance_id' => 'required|exists:patient_insurances,id',
            'patient_id' => 'required|exists:patients,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'visit_id' => 'nullable|exists:visits,id',
            'service_date' => 'required|date',
            'diagnosis' => 'nullable|string|max:1000',
            'services_description' => 'required|string|max:2000',
            'total_amount' => 'required|numeric|min:0',
            'covered_amount' => 'required|numeric|min:0',
            'patient_share' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $claim = DB::transaction(function () use ($validated) {
            $validated['claim_number'] = InsuranceClaim::generateClaimNumber();
            $validated['status'] = 'draft';
            $validated['created_by'] = auth()->id();

            return InsuranceClaim::create($validated);
        });

        AuditLogger::log('created', $claim);
        Cache::forget('insurance_claims_stats');

        return back()->with('success', 'Claim created: ' . $claim->claim_number);
    }

    public function updateStatus(Request $request, InsuranceClaim $claim)
    {
        $validStatuses = collect(InsuranceClaim::ALLOWED_TRANSITIONS)->flatten()->unique()->implode(',');

        $validated = $request->validate([
            'status' => 'required|string|in:' . $validStatuses,
            'approved_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'rejection_reason' => 'nullable|string|max:1000',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $newStatus = $validated['status'];

        // Validate transition
        $allowed = InsuranceClaim::ALLOWED_TRANSITIONS[$claim->status] ?? [];
        if (!in_array($newStatus, $allowed)) {
            return back()->with('error', "Cannot transition from {$claim->status} to {$newStatus}");
        }

        DB::transaction(function () use ($claim, $validated, $newStatus) {
            $claim->status = $newStatus;

            if ($newStatus === 'submitted') $claim->submitted_at = now();
            if (in_array($newStatus, ['approved', 'partially_approved'])) {
                $claim->approved_at = now();
                if (isset($validated['approved_amount'])) $claim->approved_amount = $validated['approved_amount'];
            }
            if (in_array($newStatus, ['paid', 'partially_paid'])) {
                $claim->paid_at = now();
                if (isset($validated['paid_amount'])) $claim->paid_amount = $validated['paid_amount'];

                // Update used_amount on patient insurance
                $claim->patientInsurance()->increment('used_amount', $validated['paid_amount'] ?? 0);
            }
            if ($newStatus === 'rejected') {
                $claim->rejection_reason = $validated['rejection_reason'] ?? null;
            }
            if (isset($validated['reference_number'])) $claim->reference_number = $validated['reference_number'];
            if (isset($validated['notes'])) $claim->notes = $validated['notes'];

            $claim->save();
        });

        AuditLogger::log('status_changed', $claim, ['new_status' => $newStatus]);
        Cache::forget('insurance_claims_stats');

        return back()->with('success', 'Claim status updated');
    }
}
