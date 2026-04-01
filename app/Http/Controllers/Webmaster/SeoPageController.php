<?php

namespace App\Http\Controllers\Webmaster;

use App\Http\Controllers\Admin\SeoPageController as AdminSeoPageController;
use App\Models\SeoPage;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SeoPageController extends AdminSeoPageController
{
    public function index(): Response
    {
        $seoPages = SeoPage::orderBy('id')->get();

        return Inertia::render('Webmaster/SeoPages/Index', [
            'seoPages' => $seoPages,
        ]);
    }

    public function edit(SeoPage $seoPage): Response
    {
        return Inertia::render('Webmaster/SeoPages/Edit', [
            'seoPage' => $seoPage,
        ]);
    }

    public function update(Request $request, SeoPage $seoPage): RedirectResponse
    {
        $validated = $request->validate([
            'title_ar' => 'nullable|string|max:500',
            'title_en' => 'nullable|string|max:500',
            'description_ar' => 'nullable|string|max:2000',
            'description_en' => 'nullable|string|max:2000',
            'keywords' => 'nullable|string|max:1000',
            'og_image' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|string|max:500',
            'is_indexable' => 'boolean',
            'structured_data' => 'nullable|string',
        ]);

        if (!empty($validated['structured_data'])) {
            $decoded = json_decode($validated['structured_data'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()->back()->withErrors(['structured_data' => 'Invalid JSON format.']);
            }
            $validated['structured_data'] = $decoded;
        } else {
            $validated['structured_data'] = null;
        }

        $seoPage->update($validated);

        AuditLogger::log('updated', $seoPage, ['page' => $seoPage->page_identifier]);

        return redirect('/webmaster/seo-pages')->with('success', 'SEO settings updated successfully.');
    }
}
