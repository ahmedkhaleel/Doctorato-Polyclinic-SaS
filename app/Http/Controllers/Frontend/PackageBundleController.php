<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PackageBundle;
use App\Services\PackageBundleWorkflowService;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PackageBundleController extends Controller
{
    public function index(): Response
    {
        $bundles = PackageBundle::active()
            ->with('services.service')
            ->orderBy('display_order')
            ->get();

        return Inertia::render('Frontend/PackageBundles/Index', [
            'bundles' => $bundles,
            'seo' => SeoService::get('package-bundles'),
        ]);
    }

    public function show(string $locale, PackageBundle $bundle): Response
    {
        if (!$bundle->is_active) {
            abort(404);
        }

        $bundle->load('services.service');

        return Inertia::render('Frontend/PackageBundles/Show', [
            'bundle' => $bundle,
        ]);
    }

    public function book(string $locale, PackageBundle $bundle): Response
    {
        if (!$bundle->is_active) {
            abort(404);
        }

        $bundle->load('services.service');

        return Inertia::render('Frontend/PackageBundles/Book', [
            'bundle' => $bundle,
        ]);
    }

    public function storeBooking(Request $request, string $locale, PackageBundle $bundle, PackageBundleWorkflowService $workflowService): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'notes' => 'nullable|string',
            'promo_code' => 'nullable|string|max:50',
        ]);

        // Determine patient_id: use authenticated patient or create a guest record
        $patientId = null;
        if (auth()->check() && auth()->user()->patient) {
            $patientId = auth()->user()->patient->id;
        } else {
            // Create or find patient by phone
            $patient = \App\Models\Patient::firstOrCreate(
                ['phone' => $data['phone']],
                [
                    'full_name' => $data['full_name'],
                    'file_number' => \App\Models\Patient::generateFileNumber(),
                    'source' => 'website',
                ]
            );
            $patientId = $patient->id;
        }

        $workflowService->createFromWebsite([
            'package_bundle_id' => $bundle->id,
            'patient_id' => $patientId,
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'notes' => $data['notes'] ?? null,
            'promo_code' => $data['promo_code'] ?? null,
        ]);

        $locale = app()->getLocale();

        return redirect("/{$locale}/package-bundles/{$bundle->id}")
            ->with('success', $locale === 'ar'
                ? 'تم تقديم طلب الحجز بنجاح. سنتواصل معك قريبا.'
                : 'Booking request submitted successfully. We will contact you soon.');
    }
}
