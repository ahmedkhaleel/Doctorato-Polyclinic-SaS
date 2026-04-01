<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DentalFollowupRule;
use App\Models\DentalScheduledFollowup;
use App\Models\DentalTreatment;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\DentalFollowupService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DentalFollowupRuleController extends Controller
{
    /**
     * Show follow-up rules management page with upcoming follow-ups.
     */
    public function index()
    {
        $rules = DentalFollowupRule::orderBy('sort_order')
            ->orderBy('treatment_type')
            ->get();

        // If no rules exist, seed defaults
        if ($rules->isEmpty()) {
            DentalFollowupService::seedDefaultRules();
            $rules = DentalFollowupRule::orderBy('sort_order')
                ->orderBy('treatment_type')
                ->get();
        }

        $upcomingFollowups = DentalScheduledFollowup::with([
                'patient:id,full_name,file_number,phone',
                'doctor:id,name_ar,name_en',
                'treatment:id,treatment_type,tooth_number',
                'rule:id,label_ar,label_en,followup_days',
                'booking:id,booking_number,status',
            ])
            ->upcoming()
            ->orderBy('scheduled_date')
            ->limit(30)
            ->get();

        $stats = [
            'total_rules'      => DentalFollowupRule::count(),
            'active_rules'     => DentalFollowupRule::active()->count(),
            'pending_followups' => DentalScheduledFollowup::pending()->count(),
            'upcoming_week'    => DentalScheduledFollowup::upcoming()
                ->whereBetween('scheduled_date', [today(), today()->addDays(7)])
                ->count(),
        ];

        return Inertia::render('Admin/Dental/FollowupRules/Index', [
            'rules'             => $rules,
            'upcomingFollowups' => $upcomingFollowups,
            'stats'             => $stats,
            'treatmentTypes'    => DentalTreatment::TYPES,
            'autoFollowupEnabled' => Setting::get('dental_auto_followup_enabled', '1') === '1',
        ]);
    }

    /**
     * Update a follow-up rule.
     */
    public function update(Request $request, DentalFollowupRule $rule)
    {
        $data = $request->validate([
            'label_ar'            => 'required|string|max:255',
            'label_en'            => 'required|string|max:255',
            'followup_days'       => 'required|integer|min:1|max:365',
            'auto_create_booking' => 'boolean',
            'sms_patient'         => 'boolean',
            'notify_doctor'       => 'boolean',
            'notify_secretary'    => 'boolean',
            'sms_days_before'     => 'integer|min:0|max:7',
            'is_active'           => 'boolean',
            'notes'               => 'nullable|string|max:500',
        ]);

        $rule->update($data);

        AuditLogger::log('updated', $rule);

        return redirect()->back()->with('success', 'تم تحديث قاعدة المتابعة بنجاح');
    }

    /**
     * Toggle rule active/inactive.
     */
    public function toggleActive(DentalFollowupRule $rule)
    {
        $rule->update(['is_active' => !$rule->is_active]);

        AuditLogger::log('updated', $rule, null,
            "Follow-up rule toggled: {$rule->treatment_type} → " . ($rule->is_active ? 'active' : 'inactive'));

        return redirect()->back()->with('success',
            $rule->is_active ? 'تم تفعيل القاعدة' : 'تم تعطيل القاعدة');
    }

    /**
     * Toggle the global auto-followup setting.
     */
    public function toggleGlobal()
    {
        $current = Setting::get('dental_auto_followup_enabled', '1');
        Setting::set('dental_auto_followup_enabled', $current === '1' ? '0' : '1', 'dental');

        return redirect()->back()->with('success',
            $current === '1' ? 'تم تعطيل المتابعة التلقائية' : 'تم تفعيل المتابعة التلقائية');
    }

    /**
     * Cancel a scheduled follow-up.
     */
    public function cancelFollowup(DentalScheduledFollowup $followup)
    {
        $followup->update(['status' => 'cancelled']);

        AuditLogger::log('updated', $followup, null, 'Scheduled follow-up cancelled');

        return redirect()->back()->with('success', 'تم إلغاء المتابعة');
    }

    /**
     * Manually trigger SMS for a specific follow-up.
     */
    public function sendSms(DentalScheduledFollowup $followup, DentalFollowupService $service)
    {
        $result = $service->sendSmsReminder($followup);

        return redirect()->back()->with(
            $result['success'] ? 'success' : 'error',
            $result['success'] ? 'تم إرسال الرسالة بنجاح' : 'فشل إرسال الرسالة: ' . ($result['message'] ?? '')
        );
    }

    /**
     * Reset and re-seed default rules.
     */
    public function seedDefaults()
    {
        DentalFollowupService::seedDefaultRules();

        return redirect()->back()->with('success', 'تم إعادة تعيين القواعد الافتراضية');
    }
}
