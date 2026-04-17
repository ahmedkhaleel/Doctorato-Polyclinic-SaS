<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class CosmeticPackage extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name_ar', 'name_en', 'procedure_id',
        'total_sessions', 'total_price', 'validity_days',
        'description', 'is_active',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function procedure() { return $this->belongsTo(CosmeticProcedure::class, 'procedure_id'); }
    public function sessions() { return $this->hasMany(CosmeticSession::class, 'package_id'); }
}
