<?php

namespace App\Http\Controllers\Webmaster;

use App\Http\Controllers\Admin\SliderController as AdminSliderController;
use App\Models\HeroSlide;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SliderController extends AdminSliderController
{
    public function index(): Response
    {
        $slides = HeroSlide::ordered()->get();

        return Inertia::render('Webmaster/Slider/Index', [
            'slides' => $slides,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Webmaster/Slider/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'is_active' => filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN),
        ]);

        $data = $request->validate([
            'title_ar' => 'nullable|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'subtitle_ar' => 'nullable|string|max:255',
            'subtitle_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string|max:500',
            'description_en' => 'nullable|string|max:500',
            'button_text_ar' => 'nullable|string|max:100',
            'button_text_en' => 'nullable|string|max:100',
            'button_link' => 'nullable|string|max:255',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:20480',
            'display_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('uploads/slider', 'public');
        }

        $data['display_order'] = $data['display_order'] ?? (HeroSlide::max('display_order') + 1);

        $slide = HeroSlide::create($data);

        AuditLogger::log('created', $slide);

        return redirect()->route('webmaster.slider.index')->with('success', 'Slide created successfully.');
    }

    public function edit(HeroSlide $heroSlide): Response
    {
        return Inertia::render('Webmaster/Slider/Edit', [
            'slide' => $heroSlide,
        ]);
    }

    public function update(Request $request, HeroSlide $heroSlide): RedirectResponse
    {
        $request->merge([
            'is_active' => filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN),
        ]);

        $data = $request->validate([
            'title_ar' => 'nullable|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'subtitle_ar' => 'nullable|string|max:255',
            'subtitle_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string|max:500',
            'description_en' => 'nullable|string|max:500',
            'button_text_ar' => 'nullable|string|max:100',
            'button_text_en' => 'nullable|string|max:100',
            'button_link' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:20480',
            'display_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            if ($heroSlide->image && !str_starts_with($heroSlide->image, 'http')) {
                Storage::disk('public')->delete($heroSlide->image);
            }
            $data['image'] = $request->file('image')->store('uploads/slider', 'public');
        }

        $heroSlide->update($data);

        AuditLogger::log('updated', $heroSlide);

        return redirect()->route('webmaster.slider.index')->with('success', 'Slide updated successfully.');
    }

    public function destroy(HeroSlide $heroSlide): RedirectResponse
    {
        if ($heroSlide->image && !str_starts_with($heroSlide->image, 'http')) {
            Storage::disk('public')->delete($heroSlide->image);
        }

        AuditLogger::log('deleted', $heroSlide);
        $heroSlide->delete();

        return redirect()->route('webmaster.slider.index')->with('success', 'Slide deleted successfully.');
    }

    public function updateOrder(Request $request): RedirectResponse
    {
        $request->validate([
            'slides' => 'required|array',
            'slides.*.id' => 'required|exists:hero_slides,id',
            'slides.*.display_order' => 'required|integer',
        ]);

        foreach ($request->slides as $item) {
            HeroSlide::where('id', $item['id'])->update(['display_order' => $item['display_order']]);
        }

        return redirect()->back()->with('success', 'Slide order updated.');
    }
}
