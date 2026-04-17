<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class CosmeticProcedure extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    const CATEGORIES = ['injectable', 'laser', 'chemical', 'mechanical', 'thread', 'other'];

    protected $fillable = [
        'name_ar', 'name_en', 'category', 'description',
        'default_price', 'default_duration_minutes', 'recovery_days',
        'is_active', 'display_order',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function packages() { return $this->hasMany(CosmeticPackage::class, 'procedure_id'); }
    public function sessions() { return $this->hasMany(CosmeticSession::class, 'procedure_id'); }

    public function scopeActive($q) { return $q->where('is_active', true); }
}
