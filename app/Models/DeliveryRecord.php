<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryRecord extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const MODE_NVD = 'nvd';

    public const MODE_CESAREAN = 'cesarean';

    public const MODE_INSTRUMENTAL = 'instrumental';

    protected $fillable = [
        'pregnancy_id', 'visit_id', 'doctor_id', 'delivery_date', 'delivery_mode',
        'place', 'gestational_age_at_delivery', 'outcome', 'baby_weight_grams',
        'baby_sex', 'apgar_1', 'apgar_5', 'complications', 'newborn_patient_id', 'notes',
        'invoice_id', 'invoice_item_id',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'gestational_age_at_delivery' => 'decimal:1',
        'baby_weight_grams' => 'integer',
        'apgar_1' => 'integer',
        'apgar_5' => 'integer',
    ];

    public function pregnancy()
    {
        return $this->belongsTo(Pregnancy::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    /** The newborn registered as a pediatric patient, if linked. */
    public function newbornPatient()
    {
        return $this->belongsTo(Patient::class, 'newborn_patient_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
