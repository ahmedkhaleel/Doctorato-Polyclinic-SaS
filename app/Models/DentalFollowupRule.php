<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DentalFollowupRule extends Model
{
    protected $fillable = [
        'treatment_type',
        'label_ar',
        'label_en',
        'followup_days',
        'auto_create_booking',
        'sms_patient',
        'notify_doctor',
        'notify_secretary',
        'sms_days_before',
        'is_active',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'followup_days' => 'integer',
        'auto_create_booking' => 'boolean',
        'sms_patient' => 'boolean',
        'notify_doctor' => 'boolean',
        'notify_secretary' => 'boolean',
        'sms_days_before' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Default follow-up rules for seeding.
     */
    const DEFAULTS = [
        'filling'              => ['days' => 7,   'ar' => 'متابعة حشو',          'en' => 'Filling Follow-up'],
        'extraction'           => ['days' => 3,   'ar' => 'متابعة خلع',          'en' => 'Extraction Follow-up'],
        'surgical_extraction'  => ['days' => 5,   'ar' => 'متابعة خلع جراحي',    'en' => 'Surgical Extraction Follow-up'],
        'root_canal'           => ['days' => 14,  'ar' => 'متابعة علاج عصب',      'en' => 'Root Canal Follow-up'],
        'crown'                => ['days' => 7,   'ar' => 'متابعة تاج',          'en' => 'Crown Follow-up'],
        'bridge'               => ['days' => 7,   'ar' => 'متابعة جسر',          'en' => 'Bridge Follow-up'],
        'implant'              => ['days' => 90,  'ar' => 'متابعة زراعة',        'en' => 'Implant Follow-up'],
        'veneer'               => ['days' => 14,  'ar' => 'متابعة قشرة',         'en' => 'Veneer Follow-up'],
        'orthodontic'          => ['days' => 30,  'ar' => 'متابعة تقويم',        'en' => 'Orthodontic Follow-up'],
        'scaling'              => ['days' => 180, 'ar' => 'متابعة تنظيف',        'en' => 'Scaling Follow-up'],
        'cleaning'             => ['days' => 180, 'ar' => 'متابعة تنظيف عادي',   'en' => 'Cleaning Follow-up'],
        'whitening'            => ['days' => 180, 'ar' => 'متابعة تبييض',        'en' => 'Whitening Follow-up'],
        'gum_treatment'        => ['days' => 14,  'ar' => 'متابعة علاج لثة',     'en' => 'Gum Treatment Follow-up'],
        'denture'              => ['days' => 7,   'ar' => 'متابعة طقم أسنان',    'en' => 'Denture Follow-up'],
        'bone_graft'           => ['days' => 30,  'ar' => 'متابعة ترقيع عظم',    'en' => 'Bone Graft Follow-up'],
        'sinus_lift'           => ['days' => 30,  'ar' => 'متابعة رفع جيب',      'en' => 'Sinus Lift Follow-up'],
        'night_guard'          => ['days' => 14,  'ar' => 'متابعة واقي ليلي',    'en' => 'Night Guard Follow-up'],
        'retainer'             => ['days' => 30,  'ar' => 'متابعة مثبت',         'en' => 'Retainer Follow-up'],
        'sealant'              => ['days' => 180, 'ar' => 'متابعة مانع تسوس',    'en' => 'Sealant Follow-up'],
        'fluoride'             => ['days' => 180, 'ar' => 'متابعة فلورايد',      'en' => 'Fluoride Follow-up'],
    ];

    public function scheduledFollowups()
    {
        return $this->hasMany(DentalScheduledFollowup::class, 'followup_rule_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForType($query, string $treatmentType)
    {
        return $query->where('treatment_type', $treatmentType);
    }
}
