<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadAssignmentRule;
use App\Models\LeadSource;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadAssignmentRuleController extends Controller
{
    public function index(): Response
    {
        $rules = LeadAssignmentRule::with(['leadSource:id,name_en,color', 'assignToUser:id,name'])
            ->orderByDesc('priority')
            ->get();

        $sources = LeadSource::active()->ordered()->get(['id', 'name_en', 'color']);
        $users = User::active()->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/CRM/AssignmentRules/Index', [
            'rules' => $rules,
            'sources' => $sources,
            'users' => $users,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'rule_type' => 'required|in:round_robin,source_based,service_based,manual',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'assign_to_user_id' => 'required|exists:users,id',
            'priority' => 'required|integer|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $rule = LeadAssignmentRule::create($data);

        AuditLogger::log('created', $rule);

        return redirect()->route('admin.assignment-rules.index')->with('success', 'Assignment rule created.');
    }

    public function update(Request $request, LeadAssignmentRule $rule): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'rule_type' => 'required|in:round_robin,source_based,service_based,manual',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'assign_to_user_id' => 'required|exists:users,id',
            'priority' => 'required|integer|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $rule->update($data);

        AuditLogger::log('updated', $rule);

        return redirect()->route('admin.assignment-rules.index')->with('success', 'Assignment rule updated.');
    }

    public function destroy(LeadAssignmentRule $rule): RedirectResponse
    {
        AuditLogger::log('deleted', $rule);

        $rule->delete();

        return redirect()->route('admin.assignment-rules.index')->with('success', 'Assignment rule deleted.');
    }
}
