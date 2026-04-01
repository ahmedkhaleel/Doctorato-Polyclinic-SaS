<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\AuditLogger;
use App\Traits\SanitizesHtml;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    use SanitizesHtml;

    public function index(): Response
    {
        $pages = Page::all();

        return Inertia::render('Admin/Pages/Index', [
            'pages' => $pages,
        ]);
    }

    public function edit(Page $page): Response
    {
        return Inertia::render('Admin/Pages/Edit', [
            'page' => $page,
        ]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $data = $request->validate([
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'content_ar' => 'nullable|string',
            'content_en' => 'nullable|string',
            'seo_title_ar' => 'nullable|string|max:255',
            'seo_title_en' => 'nullable|string|max:255',
            'seo_desc_ar' => 'nullable|string',
            'seo_desc_en' => 'nullable|string',
        ]);

        $this->sanitizeFields($data, ['content_ar', 'content_en']);

        $page->update($data);

        AuditLogger::log('updated', $page);

        return redirect()->route('admin.pages.index')->with('success', 'Page updated successfully.');
    }
}
