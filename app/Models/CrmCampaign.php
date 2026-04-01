<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class CrmCampaign extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'lead_source_id',
        'status',
        'budget',
        'actual_cost',
        'start_date',
        'end_date',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'target_audience',
        'leads_count',
        'conversions_count',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'actual_cost' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'target_audience' => 'array',
            'leads_count' => 'integer',
            'conversions_count' => 'integer',
        ];
    }

    // ─── Relationships ───────────────────────────────────

    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'campaign_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ──────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }

    // ─── Accessors ───────────────────────────────────────

    public function getConversionRateAttribute(): float
    {
        if ($this->leads_count === 0) return 0;
        return round(($this->conversions_count / $this->leads_count) * 100, 1);
    }

    public function getCostPerLeadAttribute(): float
    {
        if ($this->leads_count === 0) return 0;
        return round($this->actual_cost / $this->leads_count, 2);
    }

    public function getRoiAttribute(): float
    {
        if ($this->actual_cost == 0) return 0;
        // Simple ROI placeholder; real calculation depends on revenue tracking
        return 0;
    }
}
