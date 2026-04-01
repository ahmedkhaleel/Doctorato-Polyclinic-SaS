<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\MedicalCertificate;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MedicalCertificateController extends Controller
{
    public function index(Request $request): Response
    {
        $certificates = MedicalCertificate::with([
            'patient:id,full_name,phone,file_number',
            'doctor:id,name_en,name_ar',
        ])
            ->when($request->search, function ($q, $s) {
                $q->where('certificate_number', 'like', "%{$s}%")
                    ->orWhereHas('patient', fn ($pq) => $pq->where('full_name', 'like', "%{$s}%")
                        ->orWhere('phone', 'like', "%{$s}%"));
            })
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => MedicalCertificate::count(),
            'this_month' => MedicalCertificate::whereMonth('issue_date', now()->month)->whereYear('issue_date', now()->year)->count(),
            'sick_leaves' => MedicalCertificate::where('type', 'sick_leave')->whereMonth('issue_date', now()->month)->count(),
            'issued' => MedicalCertificate::where('status', 'issued')->count(),
        ];

        $doctors = Doctor::where('status', 'active')->select('id', 'name_en', 'name_ar')->orderBy('name_en')->get();
        $patients = []; // loaded via search in form

        return Inertia::render('Admin/MedicalCertificates/Index', [
            'certificates' => $certificates,
            'stats' => $stats,
            'doctors' => $doctors,
            'filters' => $request->only(['search', 'type', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'visit_id' => 'nullable|exists:visits,id',
            'type' => 'required|in:sick_leave,fitness,medical_report,referral_letter,follow_up',
            'issue_date' => 'required|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'days' => 'nullable|integer|min:1|max:365',
            'diagnosis' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'recommendations' => 'nullable|string|max:1000',
        ]);

        $data['certificate_number'] = MedicalCertificate::generateNumber();
        $data['created_by'] = auth()->id();
        $data['status'] = 'draft';

        // Auto-calculate days from start/end
        if (! empty($data['start_date']) && ! empty($data['end_date']) && empty($data['days'])) {
            $data['days'] = now()->parse($data['start_date'])->diffInDays(now()->parse($data['end_date'])) + 1;
        }

        $cert = MedicalCertificate::create($data);
        AuditLogger::log('medical_certificate_created', $cert);

        return back()->with('success', "Certificate {$cert->certificate_number} created.");
    }

    public function issue(MedicalCertificate $medicalCertificate): RedirectResponse
    {
        if ($medicalCertificate->status !== 'draft') {
            return back()->with('error', 'Only draft certificates can be issued.');
        }

        $medicalCertificate->update([
            'status' => 'issued',
            'issued_at' => now(),
        ]);

        AuditLogger::log('medical_certificate_issued', $medicalCertificate);

        return back()->with('success', 'Certificate issued successfully.');
    }

    public function cancel(MedicalCertificate $medicalCertificate): RedirectResponse
    {
        if ($medicalCertificate->status === 'cancelled') {
            return back()->with('error', 'Certificate is already cancelled.');
        }

        $medicalCertificate->update(['status' => 'cancelled']);
        AuditLogger::log('medical_certificate_cancelled', $medicalCertificate);

        return back()->with('success', 'Certificate cancelled.');
    }
}
