<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Referral;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Referral::with([
            'patient:id,full_name,phone,file_number',
            'referringDoctor:id,name',
            'referredToDoctor:id,name',
        ]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('referral_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn ($pq) => $pq->where('full_name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($dept = $request->input('department')) {
            $query->where(function ($q) use ($dept) {
                $q->where('from_department', $dept)->orWhere('to_department', $dept);
            });
        }

        $referrals = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'pending' => Referral::pending()->count(),
            'active' => Referral::active()->count(),
            'completed_month' => Referral::where('status', 'completed')
                ->whereMonth('completed_at', now()->month)
                ->count(),
            'urgent' => Referral::active()->where('urgency', '!=', 'routine')->count(),
        ];

        $doctors = User::whereHas('role', fn ($q) => $q->where('name', 'doctor'))
            ->where('is_active', true)
            ->select('id', 'name')
            ->get();

        return Inertia::render('Admin/Referrals/Index', [
            'referrals' => $referrals,
            'stats' => $stats,
            'doctors' => $doctors,
            'filters' => $request->only(['search', 'status', 'department']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'referring_doctor_id' => 'required|exists:users,id',
            'referred_to_doctor_id' => 'nullable|exists:users,id',
            'visit_id' => 'nullable|exists:visits,id',
            'from_department' => 'required|string|in:' . implode(',', Referral::DEPARTMENTS),
            'to_department' => 'required|string|in:' . implode(',', Referral::DEPARTMENTS),
            'urgency' => 'required|in:' . implode(',', Referral::URGENCY_LEVELS),
            'reason' => 'required|string|max:500',
            'clinical_notes' => 'nullable|string|max:2000',
            'referring_diagnosis' => 'nullable|string|max:500',
        ]);

        $referral = Referral::create(array_merge($data, [
            'referral_number' => Referral::generateReferralNumber(),
            'status' => 'pending',
        ]));

        AuditLogger::log('created', $referral);

        return redirect()->back()->with('success', "Referral #{$referral->referral_number} created.");
    }

    public function updateStatus(Request $request, Referral $referral): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:accepted,scheduled,completed,declined,cancelled',
            'response_notes' => 'nullable|string|max:2000',
            'referred_to_doctor_id' => 'nullable|exists:users,id',
            'scheduled_at' => 'nullable|date',
        ]);

        if (! $referral->canTransitionTo($data['status'])) {
            return redirect()->back()->with('error', 'Invalid status transition.');
        }

        $updates = ['status' => $data['status']];

        if ($data['response_notes'] ?? null) {
            $updates['response_notes'] = $data['response_notes'];
        }

        if ($data['referred_to_doctor_id'] ?? null) {
            $updates['referred_to_doctor_id'] = $data['referred_to_doctor_id'];
        }

        switch ($data['status']) {
            case 'accepted':
                $updates['accepted_at'] = now();
                break;
            case 'scheduled':
                $updates['scheduled_at'] = $data['scheduled_at'] ?? now();
                break;
            case 'completed':
                $updates['completed_at'] = now();
                break;
            case 'declined':
                $updates['declined_at'] = now();
                break;
        }

        $referral->update($updates);

        AuditLogger::log('status_changed', $referral, [
            'new_status' => $data['status'],
        ]);

        return redirect()->back()->with('success', 'Referral status updated.');
    }
}
