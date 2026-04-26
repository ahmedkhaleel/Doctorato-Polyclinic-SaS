<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsTemplate;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SmsTemplateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/SmsTemplates/Index', [
            'templates' => SmsTemplate::orderBy('category')->orderBy('key')->get(),
            'categories' => SmsTemplate::CATEGORIES,
        ]);
    }

    public function update(Request $request, SmsTemplate $smsTemplate): RedirectResponse
    {
        $data = $request->validate([
            'body_ar'     => 'required|string|max:1000',
            'body_en'     => 'required|string|max:1000',
            'description' => 'nullable|string|max:255',
            'is_active'   => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $smsTemplate->update($data);
        AuditLogger::log('updated', $smsTemplate);

        return redirect()->back()->with('success', 'SMS template updated.');
    }

    /**
     * Preview a template by rendering it with sample variables.
     * Used by the admin UI's "preview" button.
     */
    public function preview(Request $request, SmsTemplate $smsTemplate)
    {
        $data = $request->validate([
            'locale' => 'required|in:ar,en',
            'vars'   => 'array',
        ]);

        $vars = $data['vars'] ?? [];
        // Fill any unspecified placeholders with the placeholder name itself
        foreach ($smsTemplate->placeholders ?? [] as $p) {
            if (! array_key_exists($p, $vars) || $vars[$p] === '') {
                $vars[$p] = '<' . $p . '>';
            }
        }

        $rendered = SmsTemplate::render($smsTemplate->key, $vars, $data['locale']);
        return response()->json(['rendered' => $rendered, 'length' => mb_strlen($rendered ?? '')]);
    }
}
