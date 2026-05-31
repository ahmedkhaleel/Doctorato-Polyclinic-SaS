<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class CosmeticProcedure extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity, SoftDeletes;

    const CATEGORIES = ['injectable', 'laser', 'chemical', 'mechanical', 'thread', 'other'];

    protected $fillable = [
        'name_ar', 'name_en', 'category', 'description',
        'default_price', 'supply_id', 'default_consumption_qty',
        'default_duration_minutes', 'recovery_days',
        'is_active', 'display_order',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'default_consumption_qty' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function packages() { return $this->hasMany(CosmeticPackage::class, 'procedure_id'); }
    public function sessions() { return $this->hasMany(CosmeticSession::class, 'procedure_id'); }
    public function supply() { return $this->belongsTo(Supply::class); }

    public function scopeActive($q) { return $q->where('is_active', true); }
}
