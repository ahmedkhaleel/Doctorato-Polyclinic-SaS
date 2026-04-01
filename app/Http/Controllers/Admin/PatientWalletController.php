<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientWallet;
use App\Models\WalletTransaction;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PatientWalletController extends Controller
{
    public function index(Request $request): Response
    {
        $wallets = PatientWallet::with('patient:id,full_name,phone,file_number')
            ->when($request->search, function ($q, $s) {
                $q->whereHas('patient', fn ($pq) => $pq->where('full_name', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('file_number', 'like', "%{$s}%"));
            })
            ->when($request->filter === 'positive', fn ($q) => $q->where('balance', '>', 0))
            ->when($request->filter === 'zero', fn ($q) => $q->where('balance', '<=', 0))
            ->orderByDesc('balance')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total_wallets' => PatientWallet::count(),
            'total_balance' => (float) PatientWallet::where('balance', '>', 0)->sum('balance'),
            'positive_wallets' => PatientWallet::where('balance', '>', 0)->count(),
            'transactions_today' => WalletTransaction::whereDate('created_at', today())->count(),
        ];

        return Inertia::render('Admin/Wallets/Index', [
            'wallets' => $wallets,
            'stats' => $stats,
            'filters' => $request->only(['search', 'filter']),
        ]);
    }

    public function show(Patient $patient): Response
    {
        $wallet = PatientWallet::firstOrCreate(
            ['patient_id' => $patient->id],
            ['balance' => 0]
        );

        $transactions = WalletTransaction::with('creator:id,name')
            ->where('patient_id', $patient->id)
            ->latest()
            ->paginate(30);

        $patient->load('invoices:id,invoice_number,total,paid_amount,status');

        $outstandingBalance = (float) $patient->invoices()
            ->whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('SUM(total - paid_amount) as balance')
            ->value('balance');

        return Inertia::render('Admin/Wallets/Show', [
            'patient' => $patient->only('id', 'full_name', 'phone', 'file_number', 'email'),
            'wallet' => $wallet,
            'transactions' => $transactions,
            'outstandingBalance' => round($outstandingBalance, 2),
        ]);
    }

    public function deposit(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:999999',
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($patient, $data) {
            $wallet = PatientWallet::firstOrCreate(
                ['patient_id' => $patient->id],
                ['balance' => 0]
            );

            $wallet->increment('balance', $data['amount']);
            $wallet->refresh();

            WalletTransaction::create([
                'patient_id' => $patient->id,
                'type' => 'deposit',
                'amount' => $data['amount'],
                'balance_after' => $wallet->balance,
                'description' => $data['description'],
                'created_by' => auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);

            AuditLogger::log('wallet_deposit', $wallet, ['amount' => $data['amount']]);
        });

        return back()->with('success', 'Deposit recorded successfully.');
    }

    public function withdraw(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:999999',
            'description' => 'required|string|max:255',
            'type' => 'required|in:withdrawal,payment',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($patient, $data) {
                // Lock the wallet row to prevent concurrent overdraft
                $wallet = PatientWallet::where('patient_id', $patient->id)->lockForUpdate()->first();

                if (! $wallet || $wallet->balance < $data['amount']) {
                    throw new \RuntimeException('Insufficient wallet balance.');
                }

                $wallet->decrement('balance', $data['amount']);
            $wallet->refresh();

            WalletTransaction::create([
                'patient_id' => $patient->id,
                'type' => $data['type'],
                'amount' => -$data['amount'],
                'balance_after' => $wallet->balance,
                'description' => $data['description'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'created_by' => auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);

                AuditLogger::log('wallet_withdrawal', $wallet, ['amount' => $data['amount']]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Withdrawal processed successfully.');
    }

    public function adjust(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:-999999|max:999999',
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($data['amount'] == 0) {
            return back()->with('error', 'Adjustment amount cannot be zero.');
        }

        DB::transaction(function () use ($patient, $data) {
            $wallet = PatientWallet::firstOrCreate(
                ['patient_id' => $patient->id],
                ['balance' => 0]
            );

            $wallet->increment('balance', $data['amount']);
            $wallet->refresh();

            WalletTransaction::create([
                'patient_id' => $patient->id,
                'type' => 'adjustment',
                'amount' => $data['amount'],
                'balance_after' => $wallet->balance,
                'description' => $data['description'],
                'created_by' => auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);

            AuditLogger::log('wallet_adjustment', $wallet, ['amount' => $data['amount']]);
        });

        return back()->with('success', 'Balance adjusted.');
    }
}
