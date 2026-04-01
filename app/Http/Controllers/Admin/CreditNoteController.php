<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CreditNoteController extends Controller
{
    public function index(Request $request): Response
    {
        $notes = CreditNote::with([
            'invoice:id,invoice_number,total',
            'patient:id,full_name,phone,file_number',
            'creator:id,name',
            'approver:id,name',
        ])
            ->when($request->search, fn ($q, $s) => $q->where('credit_note_number', 'like', "%{$s}%")
                ->orWhereHas('patient', fn ($pq) => $pq->where('full_name', 'like', "%{$s}%")))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->module, fn ($q, $m) => $q->whereHas('invoice', fn ($iq) => $iq->where('module', $m)))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'pending' => CreditNote::pending()->count(),
            'pending_amount' => (float) CreditNote::pending()->sum('amount'),
            'approved_month' => CreditNote::where('status', 'approved')
                ->whereMonth('approved_at', now()->month)->count(),
            'refunded_month' => (float) CreditNote::where('status', 'refunded')
                ->whereMonth('refunded_at', now()->month)->sum('amount'),
        ];

        return Inertia::render('Admin/CreditNotes/Index', [
            'creditNotes' => $notes,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'type', 'module']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'type' => 'required|in:' . implode(',', CreditNote::TYPES),
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $cn = DB::transaction(function () use ($data) {
                $invoice = Invoice::lockForUpdate()->findOrFail($data['invoice_id']);

                // Validate amount doesn't exceed invoice total
                $existingRefunds = CreditNote::where('invoice_id', $invoice->id)
                    ->whereNotIn('status', ['rejected'])
                    ->sum('amount');

                if (($existingRefunds + $data['amount']) > $invoice->total) {
                    throw new \RuntimeException('Refund amount exceeds invoice total.');
                }

                return CreditNote::create([
                    'credit_note_number' => CreditNote::generateNumber(),
                    'invoice_id' => $invoice->id,
                    'patient_id' => $invoice->patient_id,
                    'created_by' => auth()->id(),
                    'type' => $data['type'],
                    'status' => 'draft',
                    'amount' => $data['amount'],
                    'reason' => $data['reason'],
                    'notes' => $data['notes'] ?? null,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        AuditLogger::log('created', $cn);

        return redirect()->route('admin.credit-notes.index')
            ->with('success', "Credit Note #{$cn->credit_note_number} created.");
    }

    public function updateStatus(Request $request, CreditNote $creditNote): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:pending_approval,approved,rejected,refunded',
            'refund_method' => 'nullable|in:cash,card,bank_transfer,wallet_credit',
            'notes' => 'nullable|string|max:2000',
        ]);

        if (! $creditNote->canTransitionTo($data['status'])) {
            return back()->with('error', 'Invalid status transition.');
        }

        $updates = ['status' => $data['status']];

        if ($data['notes'] ?? null) {
            $updates['notes'] = $data['notes'];
        }

        switch ($data['status']) {
            case 'approved':
                $updates['approved_by'] = auth()->id();
                $updates['approved_at'] = now();
                break;

            case 'refunded':
                $updates['refund_method'] = $data['refund_method'] ?? 'cash';
                $updates['refunded_at'] = now();

                // Update invoice paid_amount and status
                DB::transaction(function () use ($creditNote, $updates) {
                    $creditNote->update($updates);
                    $invoice = $creditNote->invoice;
                    $newPaid = max(0, $invoice->paid_amount - $creditNote->amount);
                    $invoice->update([
                        'paid_amount' => $newPaid,
                        'status' => $newPaid <= 0 ? 'refunded' : ($newPaid < $invoice->total ? 'partial' : 'paid'),
                    ]);
                });

                AuditLogger::log('refunded', $creditNote);
                return back()->with('success', 'Refund processed successfully.');
        }

        $creditNote->update($updates);
        AuditLogger::log('status_changed', $creditNote, ['new_status' => $data['status']]);

        return back()->with('success', 'Credit note status updated.');
    }
}
