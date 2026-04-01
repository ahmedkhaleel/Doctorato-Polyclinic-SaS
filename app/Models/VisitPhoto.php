<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\LogsActivity;

class VisitPhoto extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['visit_id', 'photo_path', 'photo_type', 'caption', 'taken_at'];

    protected $appends = ['url'];

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn () => '/storage/' . $this->photo_path);
    }
}
