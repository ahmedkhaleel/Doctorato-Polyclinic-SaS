<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketerCommission;
use App\Models\Lead;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MarketerCommissionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = MarketerCommission::with([
            'marketer:id,name',
            'lead:id,full_name',
            'booking:id',
            'approver:id,name',
        ]);

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $commissions = $query->latest()->paginate(20)->withQueryString();

        // Summary stats
        $stats = [
            'total_pending' => MarketerCommission::pending()->sum('commission_amount'),
            'total_approved' => MarketerCommission::approved()->sum('commission_amount'),
            'total_paid' => MarketerCommission::paid()->sum('commission_amount'),
            'pending_count' => MarketerCommission::pending()->count(),
        ];

        $marketers = User::active()->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/CRM/Commissions/Index', [
            'commissions' => $commissions,
            'stats' => $stats,
            'marketers' => $marketers,
            'filters' => $request->only(['user_id', 'status', 'date_from', 'date_to']),
        ]);
    }

    public function create(): Response
    {
        $marketers = User::active()->select('id', 'name')->orderBy('name')->get();
        $leads = Lead::converted()->with('booking:id')->latest()->limit(100)->get(['id', 'full_name', 'booking_id']);

        return Inertia::render('Admin/CRM/Commissions/Create', [
            'marketers' => $marketers,
            'leads' => $leads,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'lead_id' => 'required|exists:leads,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'commission_type' => 'required|in:fixed,percentage',
            'rate' => 'required|numeric|min:0',
            'base_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $data['status'] = 'pending';

        // Calculate commission
        if ($data['commission_type'] === 'percentage') {
            $data['commission_amount'] = round(($data['rate'] / 100) * $data['base_amount'], 2);
        } else {
            $data['commission_amount'] = $data['rate'];
        }

        MarketerCommission::create($data);

        return redirect()->route('admin.commissions.index')->with('success', 'Commission created successfully.');
    }

    public function approve(MarketerCommission $commission): RedirectResponse
    {
        if ($commission->status !== 'pending') {
            return back()->with('error', 'Only pending commissions can be approved.');
        }

        $commission->approve();

        return back()->with('success', 'Commission approved.');
    }

    public function markPaid(MarketerCommission $commission): RedirectResponse
    {
        if ($commission->status !== 'approved') {
            return back()->with('error', 'Only approved commissions can be marked as paid.');
        }

        $commission->markAsPaid();

        return back()->with('success', 'Commission marked as paid.');
    }

    public function destroy(MarketerCommission $commission): RedirectResponse
    {
        if ($commission->status === 'paid') {
            return back()->with('error', 'Paid commissions cannot be deleted.');
        }

        AuditLogger::log('deleted', $commission);
        $commission->delete();

        return redirect()->route('admin.commissions.index')->with('success', 'Commission deleted.');
    }
}
