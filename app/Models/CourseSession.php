<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** NP6 — a single session within a treatment course. Bills on completion. */
class CourseSession extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'treatment_course_id', 'patient_id', 'session_number', 'performed_at',
        'parameters', 'cost', 'invoice_id', 'invoice_item_id', 'completed_at', 'notes',
    ];

    protected $casts = [
        'performed_at' => 'date',
        'parameters' => 'array',
        'cost' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(TreatmentCourse::class, 'treatment_course_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
