<?php

namespace App\Services;

use App\Models\CosmeticPackagePurchase;
use App\Models\CosmeticSession;
use App\Models\DeliveryRecord;
use App\Models\DentalTreatment;
use App\Models\PediatricVaccination;
use App\Models\RiskAssessment;
use App\Models\ScaleResult;

/**
 * P2-2b — aggregated clinical-quality / outcomes metrics across the enabled
 * specialty modules. Read-only, computed from existing clinical event tables.
 * Every metric block is defensive (wrapped) so a schema difference in one
 * module never breaks the whole dashboard. Branch-scoped models auto-scope via
 * their global scope; cross-branch admins see the active branch (or all in
 * console). Honour the date window when given.
 */
class OutcomeService
{
    /**
     * @return array<int,array{module:string,title_en:string,title_ar:string,metrics:array}>
     */
    public function forEnabledModules(?string $from = null, ?string $to = null): array
    {
        $out = [];

        foreach (ModuleManager::MEDICAL_MODULES as $module) {
            if (! ModuleManager::isEnabled($module)) {
                continue;
            }
            $metrics = $this->metricsFor($module, $from, $to);
            if (! empty($metrics)) {
                $out[] = [
                    'module' => $module,
                    'title_en' => ucfirst($module),
                    'title_ar' => self::TITLES_AR[$module] ?? $module,
                    'metrics' => $metrics,
                ];
            }
        }

        return $out;
    }

    private const TITLES_AR = [
        'dental' => 'الأسنان', 'derma' => 'الجلدية والتجميل', 'pediatric' => 'الأطفال',
        'obgyn' => 'النساء والتوليد', 'psychiatry' => 'الطب النفسي', 'neurology' => 'المخ والأعصاب',
    ];

    private function metricsFor(string $module, ?string $from, ?string $to): array
    {
        return match ($module) {
            'dental' => $this->guard(fn () => $this->dental($from, $to)),
            'derma' => $this->guard(fn () => $this->derma($from, $to)),
            'pediatric' => $this->guard(fn () => $this->pediatric($from, $to)),
            'obgyn' => $this->guard(fn () => $this->obgyn($from, $to)),
            'psychiatry' => $this->guard(fn () => $this->scales(['phq9', 'gad7'], $from, $to, withRisk: true)),
            'neurology' => $this->guard(fn () => $this->scales(['hit6', 'mmse', 'moca', 'updrs'], $from, $to, withRisk: false)),
            default => [],
        };
    }

    /** Run a metric builder; on any error return [] so one module can't break the page. */
    private function guard(callable $fn): array
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function metric(string $en, string $ar, $value, ?string $hintEn = null, ?string $hintAr = null): array
    {
        return ['label_en' => $en, 'label_ar' => $ar, 'value' => $value, 'hint_en' => $hintEn, 'hint_ar' => $hintAr];
    }

    private function window($q, ?string $from, ?string $to, string $col)
    {
        if ($from) {
            $q->whereDate($col, '>=', $from);
        }
        if ($to) {
            $q->whereDate($col, '<=', $to);
        }

        return $q;
    }

    private function dental(?string $from, ?string $to): array
    {
        $base = fn () => $this->window(DentalTreatment::query(), $from, $to, 'created_at');
        $total = (clone $base())->count();
        $completed = (clone $base())->where('status', 'completed')->count();
        $rate = $total > 0 ? round($completed / $total * 100, 1) : 0;

        return [
            $this->metric('Treatments completed', 'علاجات مكتملة', $completed),
            $this->metric('Completion rate', 'نسبة الإكمال', $rate.'%', 'completed / all treatments', 'مكتمل / كل العلاجات'),
        ];
    }

    private function derma(?string $from, ?string $to): array
    {
        $sessions = $this->window(CosmeticSession::query(), $from, $to, 'created_at')
            ->whereNotNull('completed_at')->count();

        $pkgTotals = CosmeticPackagePurchase::query()
            ->selectRaw('COALESCE(SUM(total_sessions),0) tot, COALESCE(SUM(sessions_used),0) used')->first();
        $pkgRate = ($pkgTotals && $pkgTotals->tot > 0) ? round($pkgTotals->used / $pkgTotals->tot * 100, 1) : 0;

        return [
            $this->metric('Cosmetic sessions completed', 'جلسات تجميل مكتملة', $sessions),
            $this->metric('Package utilisation', 'استهلاك الباقات', $pkgRate.'%', 'sessions used / sessions sold', 'جلسات مستهلكة / مُباعة'),
        ];
    }

    private function pediatric(?string $from, ?string $to): array
    {
        $given = $this->window(PediatricVaccination::query(), $from, $to, 'created_at')
            ->where('status', PediatricVaccination::STATUS_GIVEN)->count();
        $missed = $this->window(PediatricVaccination::query(), $from, $to, 'created_at')
            ->where('status', PediatricVaccination::STATUS_MISSED)->count();
        $denom = $given + $missed;
        $coverage = $denom > 0 ? round($given / $denom * 100, 1) : 0;

        return [
            $this->metric('Vaccinations administered', 'تطعيمات مُعطاة', $given),
            $this->metric('Vaccination coverage', 'تغطية التطعيم', $coverage.'%', 'given / (given + missed)', 'مُعطاة / (مُعطاة + فائتة)'),
        ];
    }

    private function obgyn(?string $from, ?string $to): array
    {
        $base = fn () => $this->window(DeliveryRecord::query(), $from, $to, 'delivery_date');
        $total = (clone $base())->count();
        $live = (clone $base())->where('outcome', 'live')->count();
        $cesarean = (clone $base())->where('delivery_mode', 'cesarean')->count();
        $meanWeight = (clone $base())->whereNotNull('baby_weight_grams')->avg('baby_weight_grams');

        return [
            $this->metric('Deliveries', 'الولادات', $total),
            $this->metric('Live-birth rate', 'نسبة المواليد الأحياء', $total > 0 ? round($live / $total * 100, 1).'%' : '—'),
            $this->metric('Cesarean rate', 'نسبة القيصرية', $total > 0 ? round($cesarean / $total * 100, 1).'%' : '—'),
            $this->metric('Mean birth weight', 'متوسط وزن المولود', $meanWeight ? round($meanWeight).' g' : '—'),
        ];
    }

    /**
     * Mean symptom-scale improvement: for patients with ≥2 results of a scale,
     * average (first score − latest score). Positive = improvement (lower score).
     */
    private function scales(array $scaleKeys, ?string $from, ?string $to, bool $withRisk): array
    {
        $metrics = [];

        $assessments = $this->window(ScaleResult::query(), $from, $to, 'taken_at')
            ->whereIn('scale_key', $scaleKeys)->count();
        $metrics[] = $this->metric('Scale assessments', 'تقييمات بالمقاييس', $assessments);

        // Per-patient first vs latest delta for the primary scale.
        $primary = $scaleKeys[0];
        $rows = ScaleResult::where('scale_key', $primary)
            ->orderBy('taken_at')
            ->get(['patient_id', 'score', 'taken_at'])
            ->groupBy('patient_id');
        $deltas = [];
        foreach ($rows as $patientRows) {
            if ($patientRows->count() >= 2) {
                $deltas[] = (int) $patientRows->first()->score - (int) $patientRows->last()->score;
            }
        }
        if (! empty($deltas)) {
            $mean = round(array_sum($deltas) / count($deltas), 1);
            $metrics[] = $this->metric(
                strtoupper($primary).' mean improvement', 'متوسط تحسّن '.strtoupper($primary), $mean,
                'first − latest score, '.count($deltas).' patients (↑ better)',
                'الأول − الأخير، '.count($deltas).' مريض (↑ أفضل)'
            );
        }

        if ($withRisk) {
            $activeHigh = RiskAssessment::where('is_active', true)->whereIn('risk_level', ['moderate', 'high'])->count();
            $metrics[] = $this->metric('Active elevated-risk', 'مخاطر مرتفعة نشطة', $activeHigh, 'moderate/high & active', 'متوسطة/عالية ونشطة');
        }

        return $metrics;
    }
}
