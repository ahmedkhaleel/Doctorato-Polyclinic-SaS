<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
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
        $slugRule = ['nullable', 'string'];

        // On update, ignore the current post's slug for uniqueness check
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $slugRule[] = Rule::unique('posts', 'slug')->ignore($this->route('post'));
        } else {
            $slugRule[] = Rule::unique('posts', 'slug');
        }

        return [
            'title_ar'       => ['required', 'string', 'max:255'],
            'title_en'       => ['required', 'string', 'max:255'],
            'slug'           => $slugRule,
            'category_id'    => ['nullable', 'exists:post_categories,id'],
            'content_ar'     => ['required', 'string'],
            'content_en'     => ['required', 'string'],
            'excerpt_ar'     => ['nullable', 'string'],
            'excerpt_en'     => ['nullable', 'string'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:20480'],
            'status'         => ['required', Rule::in(['draft', 'published', 'scheduled'])],
            'is_featured'    => ['nullable', 'boolean'],
            'published_at'   => ['nullable', 'date'],
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
            // Title AR
            'title_ar.required' => __('validation.custom.title_ar.required', [], app()->getLocale()),
            'title_ar.string'   => __('validation.custom.title_ar.string', [], app()->getLocale()),
            'title_ar.max'      => __('validation.custom.title_ar.max', [], app()->getLocale()),

            // Title EN
            'title_en.required' => __('validation.custom.title_en.required', [], app()->getLocale()),
            'title_en.string'   => __('validation.custom.title_en.string', [], app()->getLocale()),
            'title_en.max'      => __('validation.custom.title_en.max', [], app()->getLocale()),

            // Slug
            'slug.string'       => __('validation.custom.slug.string', [], app()->getLocale()),
            'slug.unique'       => __('validation.custom.slug.unique', [], app()->getLocale()),

            // Category
            'category_id.exists' => __('validation.custom.category_id.exists', [], app()->getLocale()),

            // Content AR
            'content_ar.required' => __('validation.custom.content_ar.required', [], app()->getLocale()),
            'content_ar.string'   => __('validation.custom.content_ar.string', [], app()->getLocale()),

            // Content EN
            'content_en.required' => __('validation.custom.content_en.required', [], app()->getLocale()),
            'content_en.string'   => __('validation.custom.content_en.string', [], app()->getLocale()),

            // Excerpt AR
            'excerpt_ar.string' => __('validation.custom.excerpt_ar.string', [], app()->getLocale()),

            // Excerpt EN
            'excerpt_en.string' => __('validation.custom.excerpt_en.string', [], app()->getLocale()),

            // Featured Image
            'featured_image.image' => __('validation.custom.featured_image.image', [], app()->getLocale()),
            'featured_image.max'   => __('validation.custom.featured_image.max', [], app()->getLocale()),

            // Status
            'status.required' => __('validation.custom.status.required', [], app()->getLocale()),
            'status.in'       => __('validation.custom.status.in', [], app()->getLocale()),

            // Is Featured
            'is_featured.boolean' => __('validation.custom.is_featured.boolean', [], app()->getLocale()),

            // Published At
            'published_at.date' => __('validation.custom.published_at.date', [], app()->getLocale()),
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
                'title_ar'       => 'العنوان بالعربية',
                'title_en'       => 'العنوان بالإنجليزية',
                'slug'           => 'الرابط المختصر',
                'category_id'    => 'التصنيف',
                'content_ar'     => 'المحتوى بالعربية',
                'content_en'     => 'المحتوى بالإنجليزية',
                'excerpt_ar'     => 'المقتطف بالعربية',
                'excerpt_en'     => 'المقتطف بالإنجليزية',
                'featured_image' => 'الصورة البارزة',
                'status'         => 'الحالة',
                'is_featured'    => 'مقال مميز',
                'published_at'   => 'تاريخ النشر',
            ];
        }

        return [
            'title_ar'       => 'Arabic title',
            'title_en'       => 'English title',
            'slug'           => 'slug',
            'category_id'    => 'category',
            'content_ar'     => 'Arabic content',
            'content_en'     => 'English content',
            'excerpt_ar'     => 'Arabic excerpt',
            'excerpt_en'     => 'English excerpt',
            'featured_image' => 'featured image',
            'status'         => 'status',
            'is_featured'    => 'featured',
            'published_at'   => 'publish date',
        ];
    }
}
