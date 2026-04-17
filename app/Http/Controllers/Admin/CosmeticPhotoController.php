<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CosmeticPhoto;
use App\Models\CosmeticProcedure;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CosmeticPhotoController extends Controller
{
    public function index(Request $request)
    {
        $query = CosmeticPhoto::with(['patient:id,full_name,phone', 'procedure:id,name_ar,name_en']);
        if ($request->filled('patient_id')) $query->where('patient_id', $request->patient_id);
        if ($request->filled('category')) $query->where('category', $request->category);
        if ($request->filled('procedure_id')) $query->where('procedure_id', $request->procedure_id);

        return Inertia::render('Admin/Cosmetic/Gallery', [
            'photos' => $query->latest('taken_at')->latest()->paginate(24)->withQueryString(),
            'filters' => $request->only(['patient_id', 'category', 'procedure_id']),
            'categories' => CosmeticPhoto::CATEGORIES,
            'patients' => Patient::orderBy('full_name')->limit(500)->get(['id', 'full_name', 'phone']),
            'procedures' => CosmeticProcedure::where('is_active', true)->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'session_id' => 'nullable|exists:cosmetic_sessions,id',
            'procedure_id' => 'nullable|exists:cosmetic_procedures,id',
            'category' => 'required|in:before,after,progress',
            'body_area' => 'nullable|string|max:255',
            'taken_at' => 'nullable|date',
            'image' => 'required|image|max:8192',
            'notes' => 'nullable|string',
        ]);
        $path = $request->file('image')->store('cosmetic/photos', 'public');
        CosmeticPhoto::create(array_merge($data, ['image_path' => $path]));
        return back()->with('success', 'تم رفع الصورة');
    }

    public function destroy(CosmeticPhoto $photo)
    {
        if ($photo->image_path && Storage::disk('public')->exists($photo->image_path)) {
            Storage::disk('public')->delete($photo->image_path);
        }
        $photo->delete();
        return back()->with('success', 'تم الحذف');
    }
}
