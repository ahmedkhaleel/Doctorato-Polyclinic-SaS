<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\LogsActivity;

class PatientPhoto extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['patient_id', 'photo_path', 'caption', 'taken_at'];

    protected $casts = ['taken_at' => 'date'];

    protected $appends = ['url'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn () => '/storage/' . $this->photo_path);
    }
}
