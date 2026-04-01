<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadSource;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadSourceController extends Controller
{
    public function index(): Response
    {
        $sources = LeadSource::withCount('leads')
            ->ordered()
            ->get();

        return Inertia::render('Admin/CRM/Sources/Index', [
            'sources' => $sources,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:lead_sources,slug',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'channel_type' => 'required|in:online,offline,referral',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;

        $leadSource = LeadSource::create($data);

        AuditLogger::log('created', $leadSource);

        return back()->with('success', 'Lead source created successfully.');
    }

    public function update(Request $request, LeadSource $leadSource): RedirectResponse
    {
        $data = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:lead_sources,slug,' . $leadSource->id,
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'channel_type' => 'required|in:online,offline,referral',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $leadSource->update($data);

        AuditLogger::log('updated', $leadSource);

        return back()->with('success', 'Lead source updated successfully.');
    }

    public function destroy(LeadSource $leadSource): RedirectResponse
    {
        if ($leadSource->leads()->exists()) {
            return back()->with('error', 'Cannot delete source with existing leads.');
        }

        AuditLogger::log('deleted', $leadSource);

        $leadSource->delete();

        return back()->with('success', 'Lead source deleted successfully.');
    }
}
