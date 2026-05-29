<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A reusable cosmetic consent template, optionally tied to a procedure.
 * When an active template for a procedure has requires_signature = true,
 * a completed cosmetic session for that procedure requires a signed consent.
 */
class CosmeticConsentTemplate extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'procedure_id', 'title_ar', 'title_en', 'body_ar', 'body_en',
        'requires_signature', 'is_active',
    ];

    protected $casts = [
        'requires_signature' => 'boolean',
        'is_active'          => 'boolean',
    ];

    public function procedure() { return $this->belongsTo(CosmeticProcedure::class, 'procedure_id'); }

    public function scopeActive($q) { return $q->where('is_active', true); }
}
