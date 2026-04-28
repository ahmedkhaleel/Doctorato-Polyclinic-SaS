<?php

namespace App\Http\Controllers\Patient;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\ModuleManager;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Patient-portal "Browse Services" page. Shows services that are:
 *   - active (status=active)
 *   - bookable (so the "Book" button doesn't lead nowhere)
 *   - in an enabled clinic module
 *
 * Helps patients with pricing transparency + discovery before they
 * open the booking form.
 */
class PatientServicesController extends BasePatientController
{
    public function index(Request $request): Response
    {
        $module = $request->input('module');

        // Restrict to enabled modules (so a disabled `pediatric` module's
        // services don't surface to patients on the same install).
        $enabledModules = collect(ModuleManager::MEDICAL_MODULES)
            ->filter(fn ($m) => ModuleManager::isEnabled($m))
            ->values()
            ->all();

        $query = Service::active()
            ->where('bookable', true)
            ->whereIn('module', $enabledModules);

        if ($module && in_array($module, $enabledModules, true)) {
            $query->where('module', $module);
        }

        $services = $query
            ->select('id', 'category_id', 'name_ar', 'name_en', 'slug',
                     'short_desc_ar', 'short_desc_en', 'icon', 'featured_image',
                     'price', 'price_after_discount', 'session_duration_minutes',
                     'default_sessions', 'module')
            ->with('category:id,name_ar,name_en')
            ->orderBy('display_order')
            ->orderBy('name_en')
            ->get()
            ->map(fn ($s) => [
                'id'                       => $s->id,
                'name_en'                  => $s->name_en,
                'name_ar'                  => $s->name_ar,
                'slug'                     => $s->slug,
                'short_desc_en'            => $s->short_desc_en,
                'short_desc_ar'            => $s->short_desc_ar,
                'image'                    => $s->image, // appended via accessor
                'price'                    => (float) ($s->price ?? 0),
                'price_after_discount'     => $s->price_after_discount !== null
                                                ? (float) $s->price_after_discount : null,
                'session_duration_minutes' => $s->session_duration_minutes,
                'default_sessions'         => $s->default_sessions,
                'module'                   => $s->module,
                'category_id'              => $s->category_id,
                'category_name_en'         => $s->category?->name_en,
                'category_name_ar'         => $s->category?->name_ar,
            ]);

        $categories = ServiceCategory::whereIn('id', $services->pluck('category_id')->filter()->unique())
            ->orderBy('display_order')
            ->get(['id', 'name_ar', 'name_en']);

        return Inertia::render('Patient/Services/Index', [
            'services'         => $services,
            'categories'       => $categories,
            'availableModules' => $enabledModules,
            'filters'          => ['module' => $module],
        ]);
    }
}
