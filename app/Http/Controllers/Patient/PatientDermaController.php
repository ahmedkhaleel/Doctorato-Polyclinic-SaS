<?php

namespace App\Http\Controllers\Patient;

use App\Models\CosmeticPhoto;
use App\Models\DermaPhoto;
use App\Models\DermaTreatmentPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Patient-facing Dermatology & Cosmetic overview: prepaid packages and their
 * remaining session balance, treatment-plan courses with progress, a combined
 * session history, and a before/after photo gallery.
 *
 * Read-only — patients can see their own records but never mutate them.
 */
class PatientDermaController extends BasePatientController
{
    public function overview(Request $request): Response
    {
        $patient = $this->patient($request);
        $locale = $request->user() ? ($request->attributes->get('locale') ?? app()->getLocale()) : app()->getLocale();

        // ─── Prepaid packages (with remaining balance) ──────────
        $packages = $patient->cosmeticPackagePurchases()
            ->with('package:id,name_ar,name_en')
            ->latest()
            ->get()
            ->map(fn ($p) => [
                'id'            => $p->id,
                'name'          => $p->package ? ($locale === 'ar' ? $p->package->name_ar : ($p->package->name_en ?: $p->package->name_ar)) : '—',
                'total'         => (int) $p->total_sessions,
                'used'          => (int) $p->sessions_used,
                'remaining'     => $p->sessions_remaining,
                'status'        => $p->status,
                'expires_at'    => $p->expires_at?->toDateString(),
                'is_usable'     => $p->is_usable,
            ]);

        // ─── Treatment-plan courses (progress) ──────────────────
        $plans = DermaTreatmentPlan::where('patient_id', $patient->id)
            ->latest()
            ->get()
            ->map(fn ($p) => [
                'id'        => $p->id,
                'title'     => $locale === 'ar' ? $p->title_ar : ($p->title_en ?: $p->title_ar),
                'type'      => $p->session_type,
                'total'     => (int) $p->estimated_sessions,
                'completed' => (int) $p->completed_sessions,
                'progress'  => $p->progress_percentage,
                'remaining' => $p->sessions_remaining,
                'status'    => $p->status,
                'start_date'=> $p->start_date?->toDateString(),
            ]);

        // ─── Combined session history (derma + cosmetic) ────────
        $derma = $patient->dermaSessions()->with('doctor:id,name_ar,name_en')->get()
            ->map(fn ($s) => [
                'kind'      => 'derma',
                'type'      => $s->session_type,
                'area'      => $s->area_treated,
                'doctor'    => $s->doctor ? ($locale === 'ar' ? $s->doctor->name_ar : $s->doctor->name_en) : null,
                'number'    => $s->session_number,
                'date'      => optional($s->completed_at ?? $s->created_at)->toDateString(),
                'completed' => $s->completed_at !== null,
            ]);
        $cosmetic = $patient->cosmeticSessions()->with(['doctor:id,name_ar,name_en', 'procedure:id,name_ar,name_en'])->get()
            ->map(fn ($s) => [
                'kind'      => 'cosmetic',
                'type'      => $s->procedure ? ($locale === 'ar' ? $s->procedure->name_ar : ($s->procedure->name_en ?: $s->procedure->name_ar)) : 'cosmetic',
                'area'      => $s->area_treated,
                'doctor'    => $s->doctor ? ($locale === 'ar' ? $s->doctor->name_ar : $s->doctor->name_en) : null,
                'number'    => $s->session_number,
                'date'      => optional($s->completed_at ?? $s->created_at)->toDateString(),
                'completed' => $s->completed_at !== null,
            ]);
        $sessions = $derma->concat($cosmetic)
            ->sortByDesc('date')
            ->values()
            ->take(40);

        // ─── Before / after gallery ─────────────────────────────
        $photoMap = fn ($photo) => [
            'category' => $photo->category,
            'area'     => $photo->body_area,
            'date'     => $photo->taken_at?->toDateString(),
            'url'      => $photo->image_path ? '/storage/' . $photo->image_path : null,
        ];
        $gallery = DermaPhoto::where('patient_id', $patient->id)->latest('taken_at')->get()->map($photoMap)
            ->concat(CosmeticPhoto::where('patient_id', $patient->id)->latest('taken_at')->get()->map($photoMap))
            ->filter(fn ($p) => $p['url'])
            ->values();

        // ─── Active skin conditions ─────────────────────────────
        $conditions = $patient->skinConditions()->where('status', 'active')->get()
            ->map(fn ($c) => [
                'name'     => $locale === 'ar' ? $c->name_ar : ($c->name_en ?: $c->name_ar),
                'severity' => $c->severity,
                'area'     => $c->body_area,
            ]);

        return Inertia::render('Patient/Derma/Overview', [
            'packages'   => $packages,
            'plans'      => $plans,
            'sessions'   => $sessions,
            'gallery'    => $gallery,
            'conditions' => $conditions,
        ]);
    }
}
