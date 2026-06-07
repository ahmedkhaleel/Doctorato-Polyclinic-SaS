<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Shared exercise catalog (NOT branch-scoped — catalogs are shared). */
class Exercise extends Model
{
    const REGIONS = ['neck', 'shoulder', 'elbow', 'wrist', 'spine', 'hip', 'knee', 'ankle', 'core', 'general'];

    protected $fillable = [
        'name_ar', 'name_en', 'region', 'category', 'media_path', 'instructions',
        'default_sets', 'default_reps', 'default_hold_sec', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];
}
