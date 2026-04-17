<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DermaPhoto;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DermaPhotoController extends Controller
{
    public function index(Request $request)
    {
        $query = DermaPhoto::with('patient:id,name,phone');

        if ($request->filled('patient_id')) $query->where('patient_id', $request->patient_id);
        if ($request->filled('category')) $query->where('category', $request->category);

        $photos = $query->latest('taken_at')->latest()->paginate(24)->withQueryString();

        return Inertia::render('Admin/Derma/Gallery', [
            'photos' => $photos,
            'filters' => $request->only(['patient_id', 'category']),
            'categories' => DermaPhoto::CATEGORIES,
            'patients' => Patient::orderBy('name')->limit(500)->get(['id', 'name', 'phone']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'visit_id' => 'nullable|exists:visits,id',
            'session_id' => 'nullable|exists:derma_sessions,id',
            'category' => 'required|in:before,after,progress',
            'body_area' => 'nullable|string|max:255',
            'taken_at' => 'nullable|date',
            'image' => 'required|image|max:8192',
            'notes' => 'nullable|string',
        ]);
        $path = $request->file('image')->store('derma/photos', 'public');
        DermaPhoto::create(array_merge($data, ['image_path' => $path]));
        return back()->with('success', 'تم رفع الصورة');
    }

    public function destroy(DermaPhoto $photo)
    {
        if ($photo->image_path && Storage::disk('public')->exists($photo->image_path)) {
            Storage::disk('public')->delete($photo->image_path);
        }
        $photo->delete();
        return back()->with('success', 'تم الحذف');
    }
}
