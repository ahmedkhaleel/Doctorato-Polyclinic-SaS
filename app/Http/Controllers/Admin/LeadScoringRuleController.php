<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadScoringRule;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadScoringRuleController extends Controller
{
    public function index(): Response
    {
        $rules = LeadScoringRule::orderBy('event')->get();

        return Inertia::render('Admin/CRM/ScoringRules/Index', [
            'rules' => $rules,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'event' => 'required|string|in:lead_created,phone_provided,email_provided,call_answered,call_no_answer,whatsapp_replied,appointment_booked,appointment_attended,appointment_missed,visit_completed,payment_made,no_response_3_days,no_response_7_days,no_response_14_days,interested_in_service,referral_provided,follow_up_completed,follow_up_missed|unique:lead_scoring_rules,event',
            'points' => 'required|integer|between:-100,100',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $rule = LeadScoringRule::create($data);

        AuditLogger::log('created', $rule);

        return redirect()->route('admin.scoring-rules.index')->with('success', 'Scoring rule created.');
    }

    public function update(Request $request, LeadScoringRule $rule): RedirectResponse
    {
        $data = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'points' => 'required|integer|between:-100,100',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $rule->update($data);

        AuditLogger::log('updated', $rule);

        return redirect()->route('admin.scoring-rules.index')->with('success', 'Scoring rule updated.');
    }

    public function destroy(LeadScoringRule $rule): RedirectResponse
    {
        AuditLogger::log('deleted', $rule);

        $rule->delete();

        return redirect()->route('admin.scoring-rules.index')->with('success', 'Scoring rule deleted.');
    }
}
