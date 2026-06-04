<?php

namespace App\Models;

use App\Services\NeuroPsych\ScaleEngine;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A completed measurement-based-care scale (PHQ-9, GAD-7, HIT-6, …). Shared at
 * patient level. Score/severity/flag are computed from answers via ScaleEngine
 * at create time, so reports never recompute.
 */
class ScaleResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'scale_key', 'answers', 'score', 'severity', 'flag',
        'entered_by', 'neuropsych_encounter_id', 'taken_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'flag' => 'boolean',
        'taken_at' => 'datetime',
    ];

    /**
     * Build a result from raw answers, computing score/severity/flag via the
     * engine. Does not persist — caller saves.
     */
    public static function build(int $patientId, string $scaleKey, array $answers, string $enteredBy, ?int $encounterId = null): self
    {
        $score = ScaleEngine::score($scaleKey, $answers);

        return new self([
            'patient_id' => $patientId,
            'scale_key' => $scaleKey,
            'answers' => $answers,
            'score' => $score,
            'severity' => ScaleEngine::severity($scaleKey, $score)['en'] ?? null,
            'flag' => ScaleEngine::raisesFlag($scaleKey, $answers),
            'entered_by' => $enteredBy,
            'neuropsych_encounter_id' => $encounterId,
            'taken_at' => now(),
        ]);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function definition(): ?array
    {
        return ScaleEngine::definition($this->scale_key);
    }
}
