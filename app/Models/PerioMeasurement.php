<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Current periodontal status for one tooth (CV dental). Branch-aware.
 * Sites: mb/b/db (buccal) + ml/l/dl (lingual). A site PD ≥4mm is a pocket;
 * ≥6mm is severe.
 */
class PerioMeasurement extends Model
{
    use BelongsToBranch;

    const SITES = ['pd_mb', 'pd_b', 'pd_db', 'pd_ml', 'pd_l', 'pd_dl'];

    protected $fillable = [
        'patient_id', 'visit_id', 'doctor_id', 'tooth_number',
        'pd_mb', 'pd_b', 'pd_db', 'pd_ml', 'pd_l', 'pd_dl',
        'bop', 'mobility', 'recorded_at',
    ];

    protected $casts = [
        'bop' => 'array',
        'recorded_at' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** Deepest pocket across the six sites. */
    public function getMaxPdAttribute(): int
    {
        return (int) max(array_map(fn ($s) => (int) $this->{$s}, self::SITES));
    }

    /**
     * Roll up a patient's current periodontal status for the at-a-glance summary
     * (charted teeth, deepest pocket, # sites ≥4/≥6 mm, BoP sites, max mobility).
     */
    public static function summaryFor(int $patientId): array
    {
        $rows = static::where('patient_id', $patientId)->get();
        $sites4 = 0;
        $sites6 = 0;
        $maxPd = 0;
        $bop = 0;
        $maxMob = 0;
        foreach ($rows as $r) {
            foreach (self::SITES as $s) {
                $v = (int) $r->{$s};
                if ($v >= 4) {
                    $sites4++;
                }
                if ($v >= 6) {
                    $sites6++;
                }
                if ($v > $maxPd) {
                    $maxPd = $v;
                }
            }
            $bop += is_array($r->bop) ? count($r->bop) : 0;
            $maxMob = max($maxMob, (int) $r->mobility);
        }

        return [
            'charted_teeth' => $rows->count(),
            'max_pd' => $maxPd,
            'sites_4plus' => $sites4,
            'sites_6plus' => $sites6,
            'bop_sites' => $bop,
            'max_mobility' => $maxMob,
        ];
    }
}
