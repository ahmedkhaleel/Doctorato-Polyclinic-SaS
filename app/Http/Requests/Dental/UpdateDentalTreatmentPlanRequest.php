<?php

namespace App\Http\Requests\Dental;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDentalTreatmentPlanRequest extends FormRequest
{
    /**
     * Valid status transitions to prevent skipping steps.
     * Key = current status, Value = array of allowed next statuses.
     */
    private const STATUS_TRANSITIONS = [
        'draft'       => ['approved', 'cancelled'],
        'approved'    => ['in_progress', 'cancelled'],
        'in_progress' => ['completed', 'cancelled'],
        'completed'   => [],           // terminal
        'cancelled'   => ['draft'],    // allow re-opening as draft
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title_ar' => 'nullable|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'estimated_cost' => 'nullable|numeric|min:0|max:999999.99',
            'estimated_sessions' => 'nullable|integer|min:1|max:200',
            'priority' => ['nullable', 'string', Rule::in(['urgent', 'high', 'normal', 'low'])],
            'status' => ['nullable', 'string', Rule::in(['draft', 'approved', 'in_progress', 'completed', 'cancelled'])],
            'start_date' => 'nullable|date',
            'expected_end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:5000',
        ];
    }

    /**
     * Additional validation after the standard rules pass.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $newStatus = $this->input('status');
            if (!$newStatus) {
                return;
            }

            $plan = $this->route('treatmentPlan') ?? $this->route('treatment_plan');
            if (!$plan) {
                return;
            }

            $currentStatus = $plan->status;
            if ($currentStatus === $newStatus) {
                return; // no change
            }

            $allowed = self::STATUS_TRANSITIONS[$currentStatus] ?? [];
            if (!in_array($newStatus, $allowed, true)) {
                $isAr = app()->getLocale() === 'ar';
                $validator->errors()->add(
                    'status',
                    $isAr
                        ? "لا يمكن تغيير الحالة من «{$currentStatus}» إلى «{$newStatus}»"
                        : "Cannot transition from '{$currentStatus}' to '{$newStatus}'"
                );
            }
        });
    }

    public function attributes(): array
    {
        if (app()->getLocale() === 'ar') {
            return [
                'title_ar' => 'العنوان بالعربي',
                'title_en' => 'العنوان بالإنجليزي',
                'estimated_cost' => 'التكلفة التقديرية',
                'estimated_sessions' => 'عدد الجلسات',
                'priority' => 'الأولوية',
                'status' => 'الحالة',
                'start_date' => 'تاريخ البدء',
                'expected_end_date' => 'تاريخ الانتهاء المتوقع',
            ];
        }

        return [];
    }
}
