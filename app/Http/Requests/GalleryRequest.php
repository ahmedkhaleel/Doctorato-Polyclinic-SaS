<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GalleryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => 'required|string|max:255',
            'image_path' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:20480',
            'video_url' => 'nullable|url|max:255',
            'caption_ar' => 'nullable|string|max:255',
            'caption_en' => 'nullable|string|max:255',
            'is_before_after' => 'boolean',
            'before_image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:20480',
            'after_image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:20480',
            'display_order' => 'nullable|integer',
            'is_visible' => 'boolean',
        ];
    }
}
