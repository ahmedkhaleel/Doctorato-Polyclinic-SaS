<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class PaymentMethod extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['name_ar', 'name_en', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
