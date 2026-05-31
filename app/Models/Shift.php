<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use BelongsToBranch, HasFactory, LogsActivity;

    protected $fillable = ['name_ar', 'name_en', 'start_time', 'end_time', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function employeeShifts()
    {
        return $this->hasMany(EmployeeShift::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
