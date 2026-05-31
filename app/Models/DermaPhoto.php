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

    public function patient() { return $this->belongsTo(Patient::class); }
    public function session() { return $this->belongsTo(DermaSession::class, 'session_id'); }
}
