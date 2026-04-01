<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FaqRequest;
use App\Models\Faq;
use App\Services\AuditLogger;
use App\Traits\SanitizesHtml;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    use SanitizesHtml;

    public function index(Request $request): Response
    {
        $faqs = Faq::when($request->category, function ($query, $category) {
                $query->where('category', $category);
            })
            ->orderBy('display_order')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Faqs/Index', [
            'faqs' => $faqs,
            'filters' => $request->only(['category']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Faqs/Create');
    }

    public function store(FaqRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->sanitizeFields($data, ['answer_ar', 'answer_en']);

        $faq = Faq::create($data);

        AuditLogger::log('created', $faq);

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ created successfully.');
    }

    public function edit(Faq $faq): Response
    {
        return Inertia::render('Admin/Faqs/Edit', [
            'faq' => $faq,
        ]);
    }

    public function update(FaqRequest $request, Faq $faq): RedirectResponse
    {
        $data = $request->validated();

        $this->sanitizeFields($data, ['answer_ar', 'answer_en']);

        $faq->update($data);

        AuditLogger::log('updated', $faq);

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ updated successfully.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        AuditLogger::log('deleted', $faq);
        $faq->delete();

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ deleted successfully.');
    }
}
