<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class PrescriptionItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'prescription_id', 'medication_name', 'dosage',
        'frequency', 'duration', 'instructions', 'sort_order',
    ];

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }
}
