<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Models\ContactMessage;
use App\Services\LeadService;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Frontend/Contact', [
            'seo' => SeoService::get('contact'),
        ]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $message = ContactMessage::create($request->validated());

        // Auto-create or link to a CRM lead
        LeadService::createFromContactMessage($message);

        return redirect()->back()->with('success', 'Your message has been sent successfully. We will get back to you soon.');
    }
}
