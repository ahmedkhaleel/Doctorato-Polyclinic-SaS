<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Testimonial;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TestimonialController extends Controller
{
    public function index(Request $request): Response
    {
        $testimonials = Testimonial::with('service')
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('display_order')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Testimonials/Index', [
            'testimonials' => $testimonials,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create(): Response
    {
        $services = Service::active()->orderBy('display_order')->get();

        return Inertia::render('Admin/Testimonials/Create', [
            'services' => $services,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_name_ar' => 'required|string|max:255',
            'client_name_en' => 'required|string|max:255',
            'service_id' => 'nullable|exists:services,id',
            'rating' => 'required|integer|min:1|max:5',
            'review_ar' => 'required|string',
            'review_en' => 'required|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:20480',
            'video_url' => 'nullable|url|max:255',
            'status' => 'required|in:published,hidden',
            'display_order' => 'nullable|integer',
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('uploads/testimonials', 'public');
        }

        $testimonial = Testimonial::create($data);

        AuditLogger::log('created', $testimonial);

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial created successfully.');
    }

    public function edit(Testimonial $testimonial): Response
    {
        $testimonial->load('service');
        $services = Service::active()->orderBy('display_order')->get();

        return Inertia::render('Admin/Testimonials/Edit', [
            'testimonial' => $testimonial,
            'services' => $services,
        ]);
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $data = $request->validate([
            'client_name_ar' => 'required|string|max:255',
            'client_name_en' => 'required|string|max:255',
            'service_id' => 'nullable|exists:services,id',
            'rating' => 'required|integer|min:1|max:5',
            'review_ar' => 'required|string',
            'review_en' => 'required|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:20480',
            'video_url' => 'nullable|url|max:255',
            'status' => 'required|in:published,hidden',
            'display_order' => 'nullable|integer',
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('uploads/testimonials', 'public');
        }

        $testimonial->update($data);

        AuditLogger::log('updated', $testimonial);

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial updated successfully.');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        AuditLogger::log('deleted', $testimonial);
        $testimonial->delete();

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial deleted successfully.');
    }
}
