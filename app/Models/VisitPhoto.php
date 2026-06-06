<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitPhoto extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity;

    protected $fillable = ['visit_id', 'photo_path', 'photo_type', 'caption', 'taken_at'];

    protected $appends = ['url'];

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    // Signed, authenticated media URL (PHI — no longer a public /storage link).
    protected function url(): Attribute
    {
        return Attribute::get(fn () => \App\Support\SecureMedia::url($this->photo_path));
    }
}
