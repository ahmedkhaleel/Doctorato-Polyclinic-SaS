<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientDocument extends Model
{
    use HasFactory;

    const TYPE_LABELS = [
        'national_id' => ['ar' => 'هوية وطنية', 'en' => 'National ID'],
        'passport' => ['ar' => 'جواز سفر', 'en' => 'Passport'],
        'iqama' => ['ar' => 'إقامة', 'en' => 'Iqama'],
        'insurance_card' => ['ar' => 'بطاقة تأمين', 'en' => 'Insurance Card'],
        'insurance_approval' => ['ar' => 'موافقة تأمين', 'en' => 'Insurance Approval'],
        'medical_report' => ['ar' => 'تقرير طبي', 'en' => 'Medical Report'],
        'lab_result' => ['ar' => 'نتيجة مختبر', 'en' => 'Lab Result'],
        'xray_report' => ['ar' => 'تقرير أشعة', 'en' => 'X-ray Report'],
        'referral_letter' => ['ar' => 'خطاب إحالة', 'en' => 'Referral Letter'],
        'discharge_summary' => ['ar' => 'ملخص خروج', 'en' => 'Discharge Summary'],
        'consent_form' => ['ar' => 'نموذج موافقة', 'en' => 'Consent Form'],
        'signed_consent' => ['ar' => 'موافقة موقعة', 'en' => 'Signed Consent'],
        'prescription' => ['ar' => 'وصفة طبية', 'en' => 'Prescription'],
        'other' => ['ar' => 'أخرى', 'en' => 'Other'],
    ];

    protected $fillable = [
        'patient_id', 'uploaded_by', 'document_type', 'title',
        'file_path', 'original_name', 'mime_type', 'file_size',
        'document_date', 'expiry_date', 'notes',
        'is_confidential', 'source',
    ];

    protected $casts = [
        'document_date' => 'date',
        'expiry_date' => 'date',
        'is_confidential' => 'boolean',
        'file_size' => 'integer',
    ];

    protected $appends = ['file_url', 'is_expired', 'type_label'];

    // ─── Relationships ──────────────────────────────────

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ─── Accessors ──────────────────────────────────────

    // Signed, authenticated media URL (PHI — no longer a public /storage link).
    protected function fileUrl(): Attribute
    {
        return Attribute::get(fn () => \App\Support\SecureMedia::url($this->file_path));
    }

    protected function isExpired(): Attribute
    {
        return Attribute::get(fn () => $this->expiry_date && $this->expiry_date->isPast());
    }

    protected function typeLabel(): Attribute
    {
        return Attribute::get(fn () => self::TYPE_LABELS[$this->document_type] ?? ['ar' => $this->document_type, 'en' => $this->document_type]);
    }

    // ─── Scopes ─────────────────────────────────────────

    public function scopeOfType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeExpiring($query, int $daysAhead = 30)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($daysAhead))
            ->where('expiry_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now());
    }
}
