<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name_ar'        => ['required', 'string', 'max:255'],
            'name_en'        => ['required', 'string', 'max:255'],
            'category_id'    => ['required', 'exists:service_categories,id'],
            'short_desc_ar'  => ['nullable', 'string'],
            'short_desc_en'  => ['nullable', 'string'],
            'full_desc_ar'   => ['nullable', 'string'],
            'full_desc_en'   => ['nullable', 'string'],
            'featured_image' => ['nullable', 'image', 'max:20480'],
            'status'         => ['required', Rule::in(['active', 'inactive'])],
            'show_on_home'   => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom validation messages in Arabic and English.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Name AR
            'name_ar.required' => __('validation.custom.name_ar.required', [], app()->getLocale()),
            'name_ar.string'   => __('validation.custom.name_ar.string', [], app()->getLocale()),
            'name_ar.max'      => __('validation.custom.name_ar.max', [], app()->getLocale()),

            // Name EN
            'name_en.required' => __('validation.custom.name_en.required', [], app()->getLocale()),
            'name_en.string'   => __('validation.custom.name_en.string', [], app()->getLocale()),
            'name_en.max'      => __('validation.custom.name_en.max', [], app()->getLocale()),

            // Category
            'category_id.required' => __('validation.custom.category_id.required', [], app()->getLocale()),
            'category_id.exists'   => __('validation.custom.category_id.exists', [], app()->getLocale()),

            // Short Description AR
            'short_desc_ar.string' => __('validation.custom.short_desc_ar.string', [], app()->getLocale()),

            // Short Description EN
            'short_desc_en.string' => __('validation.custom.short_desc_en.string', [], app()->getLocale()),

            // Full Description AR
            'full_desc_ar.string' => __('validation.custom.full_desc_ar.string', [], app()->getLocale()),

            // Full Description EN
            'full_desc_en.string' => __('validation.custom.full_desc_en.string', [], app()->getLocale()),

            // Featured Image
            'featured_image.image' => __('validation.custom.featured_image.image', [], app()->getLocale()),
            'featured_image.max'   => __('validation.custom.featured_image.max', [], app()->getLocale()),

            // Status
            'status.required' => __('validation.custom.status.required', [], app()->getLocale()),
            'status.in'       => __('validation.custom.status.in', [], app()->getLocale()),

            // Show on Home
            'show_on_home.boolean' => __('validation.custom.show_on_home.boolean', [], app()->getLocale()),
        ];
    }

    /**
     * Get custom attribute names for error messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $locale = app()->getLocale();

        if ($locale === 'ar') {
            return [
                'name_ar'        => 'الاسم بالعربية',
                'name_en'        => 'الاسم بالإنجليزية',
                'category_id'    => 'التصنيف',
                'short_desc_ar'  => 'الوصف المختصر بالعربية',
                'short_desc_en'  => 'الوصف المختصر بالإنجليزية',
                'full_desc_ar'   => 'الوصف الكامل بالعربية',
                'full_desc_en'   => 'الوصف الكامل بالإنجليزية',
                'featured_image' => 'الصورة البارزة',
                'status'         => 'الحالة',
                'show_on_home'   => 'عرض في الصفحة الرئيسية',
            ];
        }

        return [
            'name_ar'        => 'Arabic name',
            'name_en'        => 'English name',
            'category_id'    => 'category',
            'short_desc_ar'  => 'Arabic short description',
            'short_desc_en'  => 'English short description',
            'full_desc_ar'   => 'Arabic full description',
            'full_desc_en'   => 'English full description',
            'featured_image' => 'featured image',
            'status'         => 'status',
            'show_on_home'   => 'show on home page',
        ];
    }
}
