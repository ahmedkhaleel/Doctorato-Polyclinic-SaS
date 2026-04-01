<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'type',
        'subject',
        'description',
        'metadata',
        'direction',
        'duration_seconds',
        'outcome',
        'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'duration_seconds' => 'integer',
        ];
    }

    // ─── Constants ───────────────────────────────────────

    const TYPE_NOTE = 'note';
    const TYPE_CALL = 'call';
    const TYPE_WHATSAPP = 'whatsapp';
    const TYPE_EMAIL = 'email';
    const TYPE_SMS = 'sms';
    const TYPE_MEETING = 'meeting';
    const TYPE_STATUS_CHANGE = 'status_change';
    const TYPE_ASSIGNMENT = 'assignment';
    const TYPE_FOLLOW_UP_SCHEDULED = 'follow_up_scheduled';
    const TYPE_FOLLOW_UP_COMPLETED = 'follow_up_completed';
    const TYPE_BOOKING_CREATED = 'booking_created';
    const TYPE_VISIT_COMPLETED = 'visit_completed';
    const TYPE_PAYMENT_RECEIVED = 'payment_received';
    const TYPE_SYSTEM = 'system';

    // ─── Relationships ───────────────────────────────────

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // ─── Helpers ─────────────────────────────────────────

    public function getDurationFormattedAttribute(): ?string
    {
        if (! $this->duration_seconds) return null;

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        return $minutes > 0
            ? "{$minutes}m {$seconds}s"
            : "{$seconds}s";
    }

    public static function logStatusChange(Lead $lead, string $oldStatus, string $newStatus, ?int $userId = null): self
    {
        return static::create([
            'lead_id' => $lead->id,
            'type' => self::TYPE_STATUS_CHANGE,
            'subject' => "Status changed from {$oldStatus} to {$newStatus}",
            'metadata' => [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
            'performed_by' => $userId ?? auth()->id(),
        ]);
    }

    public static function logAssignment(Lead $lead, ?int $oldUserId, int $newUserId, ?int $performedBy = null): self
    {
        return static::create([
            'lead_id' => $lead->id,
            'type' => self::TYPE_ASSIGNMENT,
            'subject' => 'Lead reassigned',
            'metadata' => [
                'old_assigned_to' => $oldUserId,
                'new_assigned_to' => $newUserId,
            ],
            'performed_by' => $performedBy ?? auth()->id(),
        ]);
    }
}
