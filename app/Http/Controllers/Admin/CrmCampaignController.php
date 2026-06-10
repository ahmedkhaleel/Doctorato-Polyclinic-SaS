<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CrmCampaign;
use App\Models\LeadSource;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CrmCampaignController extends Controller
{
    public function index(Request $request): Response
    {
        $query = CrmCampaign::with(['leadSource:id,name_en,name_ar,color', 'creator:id,name']);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $campaigns = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => CrmCampaign::count(),
            'active' => CrmCampaign::active()->count(),
            'total_budget' => CrmCampaign::sum('budget'),
            'total_leads' => CrmCampaign::sum('leads_count'),
            'total_conversions' => CrmCampaign::sum('conversions_count'),
        ];

        return Inertia::render('Admin/CRM/Campaigns/Index', [
            'campaigns' => $campaigns,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        $sources = LeadSource::active()->ordered()->get(['id', 'name_en', 'name_ar']);

        return Inertia::render('Admin/CRM/Campaigns/Create', [
            'sources' => $sources,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'status' => 'required|in:draft,active,paused,completed',
            'budget' => 'nullable|numeric|min:0',
            'actual_cost' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'utm_source' => 'nullable|string|max:100',
            'utm_medium' => 'nullable|string|max:100',
            'utm_campaign' => 'nullable|string|max:100',
        ]);

        $data['budget'] = $data['budget'] ?? 0;
        $data['actual_cost'] = $data['actual_cost'] ?? 0;
        $data['created_by'] = auth()->id();

        $campaign = CrmCampaign::create($data);

        return redirect()->route('admin.campaigns.index')->with('success', 'Campaign created successfully.');
    }

    public function show(CrmCampaign $campaign): Response
    {
        $campaign->load(['leadSource', 'creator:id,name']);

        $leads = $campaign->leads()
            ->with(['source:id,name_en,icon,color', 'assignedUser:id,name'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Admin/CRM/Campaigns/Show', [
            'campaign' => $campaign,
            'leads' => $leads,
            // CRM-1: real attributed revenue/ROI (converted lead → patient → invoices)
            'roiSummary' => (new \App\Services\Crm\CrmRevenueService)->campaignRoi($campaign->id),
        ]);
    }

    public function edit(CrmCampaign $campaign): Response
    {
        $sources = LeadSource::active()->ordered()->get(['id', 'name_en', 'name_ar']);

        return Inertia::render('Admin/CRM/Campaigns/Edit', [
            'campaign' => $campaign,
            'sources' => $sources,
        ]);
    }

    public function update(Request $request, CrmCampaign $campaign): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'status' => 'required|in:draft,active,paused,completed',
            'budget' => 'nullable|numeric|min:0',
            'actual_cost' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'utm_source' => 'nullable|string|max:100',
            'utm_medium' => 'nullable|string|max:100',
            'utm_campaign' => 'nullable|string|max:100',
        ]);

        $data['budget'] = $data['budget'] ?? 0;
        $data['actual_cost'] = $data['actual_cost'] ?? 0;

        $campaign->update($data);

        return redirect()->route('admin.campaigns.show', $campaign)->with('success', 'Campaign updated successfully.');
    }

    public function destroy(CrmCampaign $campaign): RedirectResponse
    {
        AuditLogger::log('deleted', $campaign);
        $campaign->delete();

        return redirect()->route('admin.campaigns.index')->with('success', 'Campaign deleted successfully.');
    }
}
