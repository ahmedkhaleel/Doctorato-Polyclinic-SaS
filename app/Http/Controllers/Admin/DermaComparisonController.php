<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CosmeticPhoto;
use App\Models\DermaPhoto;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Before/After comparison workspace: pick a patient and view their derma +
 * cosmetic photos grouped by body area, pairing the latest "before" with the
 * latest "after" for a side-by-side / slider compare.
 */
class DermaComparisonController extends Controller
{
    public function index(Request $request)
    {
        $patientId = $request->input('patient_id');
        $groups = [];
        $patient = null;

        if ($patientId) {
            $patient = Patient::find($patientId);
        }

        if ($patient) {
            $map = fn ($p, $src) => [
                'id'       => $src . '-' . $p->id,
                'category' => $p->category,
                'area'     => $p->body_area ?: '—',
                'date'     => $p->taken_at?->toDateString(),
                'url'      => $p->image_path ? '/storage/' . $p->image_path : null,
                'notes'    => $p->notes,
            ];

            $photos = DermaPhoto::where('patient_id', $patient->id)->get()->map(fn ($p) => $map($p, 'd'))
                ->concat(CosmeticPhoto::where('patient_id', $patient->id)->get()->map(fn ($p) => $map($p, 'c')))
                ->filter(fn ($p) => $p['url'])
                ->values();

            // Group by body area; expose ordered before / after / progress sets.
            $groups = $photos->groupBy('area')->map(function ($items, $area) {
                $byCat = fn ($cat) => $items->where('category', $cat)->sortBy('date')->values()->all();
                $before = $byCat('before');
                $after = $byCat('after');

                return [
                    'area'     => $area,
                    'before'   => $before,
                    'after'    => $after,
                    'progress' => $byCat('progress'),
                    // Best pair for the slider: latest before + latest after.
                    'pair'     => [
                        'before' => end($before) ?: null,
                        'after'  => end($after) ?: null,
                    ],
                ];
            })->values();
        }

        // Quick list of patients who have any derma/cosmetic photos.
        $withPhotos = DermaPhoto::distinct()->pluck('patient_id')
            ->merge(CosmeticPhoto::distinct()->pluck('patient_id'))
            ->filter()->unique()->take(300);

        return Inertia::render('Admin/Derma/Comparisons/Index', [
            'patient'  => $patient ? $patient->only(['id', 'full_name', 'file_number', 'phone']) : null,
            'groups'   => $groups,
            'patients' => Patient::whereIn('id', $withPhotos)->orderBy('full_name')->get(['id', 'full_name', 'phone', 'file_number']),
        ]);
    }
}
