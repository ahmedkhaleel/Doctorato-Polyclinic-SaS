<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class ServiceCategory extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'module', 'name_ar', 'name_en', 'slug', 'display_order',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'category_id')->orderBy('display_order');
    }

    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeDental($query)
    {
        return $query->where('module', 'dental');
    }

    public function scopeDerma($query)
    {
        return $query->where('module', 'derma');
    }
}
