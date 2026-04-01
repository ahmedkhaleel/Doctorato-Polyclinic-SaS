<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Penalty;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PenaltyController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Penalty::with(['employee.user:id,name', 'employee.department:id,name_en']);

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $penalties = $query->latest()->paginate(15)->withQueryString();

        $employees = Employee::active()->with('user:id,name')->get()->map(fn ($e) => [
            'id' => $e->id,
            'name' => $e->user->name ?? 'Unknown',
        ]);

        $stats = [
            'total_penalties' => Penalty::penalties()->count(),
            'total_rewards' => Penalty::rewards()->count(),
            'penalties_amount' => Penalty::penalties()->sum('amount'),
            'rewards_amount' => Penalty::rewards()->sum('amount'),
        ];

        return Inertia::render('Admin/Penalties/Index', [
            'penalties' => $penalties,
            'employees' => $employees,
            'filters' => $request->only('type'),
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:penalty,reward',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string',
            'date' => 'required|date',
        ]);

        $data['created_by'] = auth()->id();

        $penalty = Penalty::create($data);

        AuditLogger::log('created', $penalty);

        $label = $data['type'] === 'penalty' ? 'Penalty' : 'Reward';
        return back()->with('success', "{$label} recorded.");
    }

    public function destroy(Penalty $penalty): RedirectResponse
    {
        if ($penalty->applied_to_salary) {
            return back()->with('error', 'Cannot delete -- already applied to salary.');
        }

        AuditLogger::log('deleted', $penalty);
        $penalty->delete();

        return back()->with('success', 'Record deleted.');
    }
}
