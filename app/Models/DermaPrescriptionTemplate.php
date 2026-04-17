<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class DermaPrescriptionTemplate extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name_ar', 'name_en', 'condition_category',
        'diagnosis_ar', 'diagnosis_en',
        'items', 'notes_ar', 'notes_en',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'items' => 'array',
        'is_active' => 'boolean',
    ];
}
