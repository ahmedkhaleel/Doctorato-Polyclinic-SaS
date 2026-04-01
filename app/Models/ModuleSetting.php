<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleSetting extends Model
{
    protected $fillable = [
        'module',
        'key',
        'value',
        'type',
        'label_ar',
        'label_en',
        'group',
        'display_order',
    ];

    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeForGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
}
