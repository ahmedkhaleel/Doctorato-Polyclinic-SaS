<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GalleryRequest;
use App\Models\Gallery;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    public function index(Request $request): Response
    {
        $galleryItems = Gallery::when($request->category, function ($query, $category) {
                $query->where('category', $category);
            })
            ->orderBy('display_order')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Gallery/Index', [
            'galleryItems' => $galleryItems,
            'filters' => $request->only(['category']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Gallery/Create');
    }

    public function store(GalleryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image_path')) {
            $data['image_path'] = $request->file('image_path')->store('uploads/gallery', 'public');
        }

        if ($request->hasFile('before_image')) {
            $data['before_image'] = $request->file('before_image')->store('uploads/gallery', 'public');
        }

        if ($request->hasFile('after_image')) {
            $data['after_image'] = $request->file('after_image')->store('uploads/gallery', 'public');
        }

        $gallery = Gallery::create($data);

        AuditLogger::log('created', $gallery);

        return redirect()->route('admin.gallery.index')->with('success', 'Gallery item created successfully.');
    }

    public function edit(Gallery $gallery): Response
    {
        return Inertia::render('Admin/Gallery/Edit', [
            'galleryItem' => $gallery,
        ]);
    }

    public function update(GalleryRequest $request, Gallery $gallery): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image_path')) {
            $data['image_path'] = $request->file('image_path')->store('uploads/gallery', 'public');
        }

        if ($request->hasFile('before_image')) {
            $data['before_image'] = $request->file('before_image')->store('uploads/gallery', 'public');
        }

        if ($request->hasFile('after_image')) {
            $data['after_image'] = $request->file('after_image')->store('uploads/gallery', 'public');
        }

        $gallery->update($data);

        AuditLogger::log('updated', $gallery);

        return redirect()->route('admin.gallery.index')->with('success', 'Gallery item updated successfully.');
    }

    public function destroy(Gallery $gallery): RedirectResponse
    {
        AuditLogger::log('deleted', $gallery);
        $gallery->delete();

        return redirect()->route('admin.gallery.index')->with('success', 'Gallery item deleted successfully.');
    }
}
