<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunicationTemplate;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommunicationTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $query = CommunicationTemplate::query();

        if ($channel = $request->input('channel')) {
            $query->where('channel', $channel);
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        $templates = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/CRM/Templates/Index', [
            'templates' => $templates,
            'filters' => $request->only(['channel', 'category']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/CRM/Templates/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'channel' => 'required|in:whatsapp,sms,email',
            'category' => 'required|in:welcome,follow_up,appointment_reminder,promotion,re_engagement,thank_you,custom',
            'subject' => 'nullable|string|max:255',
            'body_en' => 'required|string',
            'body_ar' => 'required|string',
            'variables' => 'nullable|array',
        ]);

        $data['created_by'] = auth()->id();

        $template = CommunicationTemplate::create($data);

        AuditLogger::log('created', $template);

        return redirect()->route('admin.templates.index')->with('success', 'Template created successfully.');
    }

    public function edit(CommunicationTemplate $template): Response
    {
        return Inertia::render('Admin/CRM/Templates/Edit', [
            'template' => $template,
        ]);
    }

    public function update(Request $request, CommunicationTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'channel' => 'required|in:whatsapp,sms,email',
            'category' => 'required|in:welcome,follow_up,appointment_reminder,promotion,re_engagement,thank_you,custom',
            'subject' => 'nullable|string|max:255',
            'body_en' => 'required|string',
            'body_ar' => 'required|string',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $template->update($data);

        AuditLogger::log('updated', $template);

        return redirect()->route('admin.templates.index')->with('success', 'Template updated successfully.');
    }

    public function destroy(CommunicationTemplate $template): RedirectResponse
    {
        AuditLogger::log('deleted', $template);

        $template->delete();

        return redirect()->route('admin.templates.index')->with('success', 'Template deleted successfully.');
    }
}
