<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'name_ar', 'name_en', 'code', 'phone', 'address', 'timezone', 'is_active', 'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /** Staff assigned to this branch (B3). */
    public function users()
    {
        return $this->belongsToMany(User::class, 'branch_user')->withPivot('is_primary')->withTimestamps();
    }

    /** Doctors working at this branch (B3). */
    public function doctors()
    {
        return $this->belongsToMany(Doctor::class, 'branch_doctor')->withTimestamps();
    }

    /** Localised display name for the current locale. */
    public function name(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'ar' ? $this->name_ar : $this->name_en;
    }
}
