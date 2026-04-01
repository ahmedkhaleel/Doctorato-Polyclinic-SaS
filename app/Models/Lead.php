<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class Lead extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'full_name',
        'phone',
        'phone2',
        'email',
        'gender',
        'date_of_birth',
        'city',
        'nationality',
        'status',
        'module',
        'priority',
        'score',
        'lead_source_id',
        'campaign_id',
        'referral_code',
        'assigned_to',
        'assigned_at',
        'interested_services',
        'notes',
        'patient_id',
        'booking_id',
        'converted_at',
        'loss_reason',
        'lost_at',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'landing_page',
        'ip_address',
        'last_contacted_at',
        'next_follow_up_at',
        'follow_up_count',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'assigned_at' => 'datetime',
            'converted_at' => 'datetime',
            'lost_at' => 'datetime',
            'last_contacted_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
            'interested_services' => 'array',
            'priority' => 'integer',
            'score' => 'integer',
            'follow_up_count' => 'integer',
        ];
    }

    // ─── Constants ───────────────────────────────────────

    const STATUS_NEW = 'new';
    const STATUS_CONTACTED = 'contacted';
    const STATUS_QUALIFIED = 'qualified';
    const STATUS_APPOINTMENT_BOOKED = 'appointment_booked';
    const STATUS_CONSULTATION_DONE = 'consultation_done';
    const STATUS_NEGOTIATION = 'negotiation';
    const STATUS_CONVERTED = 'converted';
    const STATUS_LOST = 'lost';
    const STATUS_DORMANT = 'dormant';

    const PRIORITY_HOT = 1;
    const PRIORITY_WARM = 2;
    const PRIORITY_COLD = 3;

    const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_CONTACTED,
        self::STATUS_QUALIFIED,
        self::STATUS_APPOINTMENT_BOOKED,
        self::STATUS_CONSULTATION_DONE,
        self::STATUS_NEGOTIATION,
        self::STATUS_CONVERTED,
        self::STATUS_LOST,
        self::STATUS_DORMANT,
    ];

    const PIPELINE_STATUSES = [
        self::STATUS_NEW,
        self::STATUS_CONTACTED,
        self::STATUS_QUALIFIED,
        self::STATUS_APPOINTMENT_BOOKED,
        self::STATUS_CONSULTATION_DONE,
        self::STATUS_NEGOTIATION,
    ];

    // ─── Relationships ───────────────────────────────────

    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(CrmCampaign::class, 'campaign_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(LeadFollowUp::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(MarketerCommission::class);
    }

    // ─── Scopes ──────────────────────────────────────────

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_NEW);
    }

    public function scopeConverted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONVERTED);
    }

    public function scopeLost(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_LOST);
    }

    public function scopeInPipeline(Builder $query): Builder
    {
        return $query->whereIn('status', self::PIPELINE_STATUSES);
    }

    public function scopeHot(Builder $query): Builder
    {
        return $query->where('priority', self::PRIORITY_HOT);
    }

    public function scopeWarm(Builder $query): Builder
    {
        return $query->where('priority', self::PRIORITY_WARM);
    }

    public function scopeCold(Builder $query): Builder
    {
        return $query->where('priority', self::PRIORITY_COLD);
    }

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeOverdueFollowUp(Builder $query): Builder
    {
        return $query->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<', now())
            ->whereNotIn('status', [self::STATUS_CONVERTED, self::STATUS_LOST]);
    }

    public function scopeNeedsAttention(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_NEW)
              ->orWhere(function ($q2) {
                  $q2->whereNotNull('next_follow_up_at')
                     ->where('next_follow_up_at', '<', now());
              });
        })->whereNotIn('status', [self::STATUS_CONVERTED, self::STATUS_LOST]);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('full_name', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('city', 'like', "%{$term}%");
        });
    }

    // ─── Accessors ───────────────────────────────────────

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_HOT => 'Hot',
            self::PRIORITY_WARM => 'Warm',
            self::PRIORITY_COLD => 'Cold',
            default => 'Unknown',
        };
    }

    public function getIsConvertedAttribute(): bool
    {
        return $this->status === self::STATUS_CONVERTED;
    }

    public function getDaysSinceLastContactAttribute(): ?int
    {
        if (! $this->last_contacted_at) return null;
        return $this->last_contacted_at->diffInDays(now());
    }

    // ─── Methods ─────────────────────────────────────────

    public function markAsContacted(): void
    {
        $this->update([
            'last_contacted_at' => now(),
            'follow_up_count' => $this->follow_up_count + 1,
        ]);
    }

    public function convertToPatient(Patient $patient, ?Booking $booking = null): void
    {
        $this->update([
            'status' => self::STATUS_CONVERTED,
            'patient_id' => $patient->id,
            'booking_id' => $booking?->id,
            'converted_at' => now(),
        ]);

        // Update campaign conversion counter
        if ($this->campaign_id) {
            $this->campaign?->increment('conversions_count');
        }
    }

    public function markAsLost(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_LOST,
            'loss_reason' => $reason,
            'lost_at' => now(),
        ]);
    }

    public function addScore(int $points): void
    {
        $this->increment('score', $points);
    }

    public function assignTo(int $userId): void
    {
        $this->update([
            'assigned_to' => $userId,
            'assigned_at' => now(),
        ]);

        // Send notification to the assigned user
        $user = User::find($userId);
        if ($user) {
            $user->notify(new \App\Notifications\LeadAssignedNotification($this, auth()->user()?->name));
        }
    }
}
