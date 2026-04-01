<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class SupplyCategory extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name_en', 'name_ar', 'icon', 'color',
        'description', 'display_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function supplies()
    {
        return $this->hasMany(Supply::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name_en');
    }
}
