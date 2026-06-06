<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DermaPhoto extends Model
{
    use BelongsToBranch;
    use HasFactory;

    const CATEGORIES = ['before', 'after', 'progress'];

    protected $fillable = [
        'patient_id', 'visit_id', 'session_id',
        'category', 'body_area', 'taken_at', 'image_path', 'notes',
    ];

    protected $casts = [
        'taken_at' => 'date',
    ];

    protected $appends = ['url'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function session()
    {
        return $this->belongsTo(DermaSession::class, 'session_id');
    }

    // Signed, authenticated media URL (PHI — no longer a public /storage link).
    public function getUrlAttribute(): ?string
    {
        return \App\Support\SecureMedia::url($this->image_path);
    }
}
