<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Shared catalog of prepaid physiotherapy session packages (not branch-scoped). */
class PhysioPackage extends Model
{
    protected $fillable = ['name_ar', 'name_en', 'total_sessions', 'price', 'validity_days', 'is_active'];

    protected $casts = ['price' => 'decimal:2', 'is_active' => 'boolean'];
}
