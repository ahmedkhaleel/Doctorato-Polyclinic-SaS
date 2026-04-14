<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CrmSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CrmSettingsController extends Controller
{
    /**
     * Display the CRM settings page.
     */
    public function index(): Response
    {
        $settings = CrmSetting::allFlat();

        return Inertia::render('Admin/CRM/Settings', [
            'settings' => $settings,
        ]);
    }

    /**
     * Bulk update CRM settings via POST.
     */
    public function update(Request $request)
    {
        $request->validate([
            'sla_response_target_minutes'  => 'nullable|integer|min:1|max:1440',
            'sla_followup_target_hours'    => 'nullable|integer|min:1|max:720',
            'stale_lead_days'              => 'nullable|integer|min:1|max:365',
            'auto_assign_enabled'          => 'nullable|boolean',
            'auto_assign_method'           => 'nullable|string|in:round_robin,load_based',
            'default_lead_priority'        => 'nullable|integer|in:1,2,3',
            'default_lead_module'          => 'nullable|string|in:derma,dental,pediatric',
            'notify_on_new_lead'           => 'nullable|boolean',
            'notify_on_status_change'      => 'nullable|boolean',
            'notify_on_overdue_followup'   => 'nullable|boolean',
        ]);

        $allowedKeys = [
            'sla_response_target_minutes',
            'sla_followup_target_hours',
            'stale_lead_days',
            'auto_assign_enabled',
            'auto_assign_method',
            'default_lead_priority',
            'default_lead_module',
            'notify_on_new_lead',
            'notify_on_status_change',
            'notify_on_overdue_followup',
        ];

        foreach ($allowedKeys as $key) {
            if ($request->has($key)) {
                CrmSetting::set($key, $request->input($key));
            }
        }

        return redirect()->route('admin.crm-settings')->with('success', 'CRM settings updated successfully.');
    }
}
