<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DentalTreatmentPlanTemplate extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'category',
        'description_ar',
        'description_en',
        'treatments',
        'estimated_cost',
        'estimated_sessions',
        'priority',
        'notes',
        'is_active',
        'sort_order',
        'usage_count',
        'created_by',
    ];

    protected $casts = [
        'treatments' => 'array',
        'estimated_cost' => 'decimal:2',
        'estimated_sessions' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'usage_count' => 'integer',
    ];

    // Categories
    const CATEGORY_GENERAL = 'general';
    const CATEGORY_ORTHODONTIC = 'orthodontic';
    const CATEGORY_IMPLANT = 'implant';
    const CATEGORY_COSMETIC = 'cosmetic';
    const CATEGORY_RESTORATIVE = 'restorative';
    const CATEGORY_SURGICAL = 'surgical';
    const CATEGORY_PERIODONTAL = 'periodontal';
    const CATEGORY_PEDIATRIC = 'pediatric';

    const CATEGORIES = [
        self::CATEGORY_GENERAL,
        self::CATEGORY_ORTHODONTIC,
        self::CATEGORY_IMPLANT,
        self::CATEGORY_COSMETIC,
        self::CATEGORY_RESTORATIVE,
        self::CATEGORY_SURGICAL,
        self::CATEGORY_PERIODONTAL,
        self::CATEGORY_PEDIATRIC,
    ];

    /**
     * Default templates to seed.
     */
    const DEFAULTS = [
        [
            'name_ar' => 'خطة تقويم شاملة',
            'name_en' => 'Comprehensive Orthodontic Plan',
            'category' => 'orthodontic',
            'description_ar' => 'خطة تقويم أسنان كاملة تشمل التركيب والمتابعة والمثبتات',
            'description_en' => 'Full orthodontic treatment plan including brackets, follow-ups and retainers',
            'estimated_sessions' => 24,
            'priority' => 'normal',
            'treatments' => [
                ['treatment_type' => 'scaling', 'description' => 'Pre-orthodontic cleaning', 'cost' => 50, 'lab_cost' => 0, 'sort_order' => 1],
                ['treatment_type' => 'orthodontic', 'description' => 'Upper arch brackets placement', 'cost' => 500, 'lab_cost' => 200, 'sort_order' => 2],
                ['treatment_type' => 'orthodontic', 'description' => 'Lower arch brackets placement', 'cost' => 500, 'lab_cost' => 200, 'sort_order' => 3],
                ['treatment_type' => 'retainer', 'description' => 'Upper retainer after treatment', 'cost' => 150, 'lab_cost' => 80, 'sort_order' => 4],
                ['treatment_type' => 'retainer', 'description' => 'Lower retainer after treatment', 'cost' => 150, 'lab_cost' => 80, 'sort_order' => 5],
            ],
        ],
        [
            'name_ar' => 'خطة زراعة أسنان كاملة',
            'name_en' => 'Full Dental Implant Plan',
            'category' => 'implant',
            'description_ar' => 'زراعة سن واحد تشمل الزرعة والدعامة والتاج',
            'description_en' => 'Single tooth implant with abutment and crown',
            'estimated_sessions' => 5,
            'priority' => 'high',
            'treatments' => [
                ['treatment_type' => 'extraction', 'description' => 'Extract damaged tooth if needed', 'cost' => 80, 'lab_cost' => 0, 'sort_order' => 1],
                ['treatment_type' => 'bone_graft', 'description' => 'Bone graft if needed', 'cost' => 300, 'lab_cost' => 150, 'sort_order' => 2],
                ['treatment_type' => 'implant', 'description' => 'Implant fixture placement', 'cost' => 800, 'lab_cost' => 400, 'sort_order' => 3],
                ['treatment_type' => 'implant', 'description' => 'Abutment placement (after osseointegration)', 'cost' => 200, 'lab_cost' => 100, 'sort_order' => 4],
                ['treatment_type' => 'crown', 'description' => 'Final crown on implant', 'cost' => 400, 'lab_cost' => 250, 'sort_order' => 5],
            ],
        ],
        [
            'name_ar' => 'خطة تجميل ابتسامة',
            'name_en' => 'Smile Makeover Plan',
            'category' => 'cosmetic',
            'description_ar' => 'تجميل الأسنان الأمامية بالقشور الخزفية والتبييض',
            'description_en' => 'Anterior teeth veneers with whitening',
            'estimated_sessions' => 4,
            'priority' => 'normal',
            'treatments' => [
                ['treatment_type' => 'scaling', 'description' => 'Professional cleaning', 'cost' => 50, 'lab_cost' => 0, 'sort_order' => 1],
                ['treatment_type' => 'whitening', 'description' => 'In-office teeth whitening', 'cost' => 300, 'lab_cost' => 50, 'sort_order' => 2],
                ['treatment_type' => 'veneer', 'tooth_number' => 11, 'description' => 'Porcelain veneer - upper right central', 'cost' => 350, 'lab_cost' => 200, 'sort_order' => 3],
                ['treatment_type' => 'veneer', 'tooth_number' => 12, 'description' => 'Porcelain veneer - upper right lateral', 'cost' => 350, 'lab_cost' => 200, 'sort_order' => 4],
                ['treatment_type' => 'veneer', 'tooth_number' => 21, 'description' => 'Porcelain veneer - upper left central', 'cost' => 350, 'lab_cost' => 200, 'sort_order' => 5],
                ['treatment_type' => 'veneer', 'tooth_number' => 22, 'description' => 'Porcelain veneer - upper left lateral', 'cost' => 350, 'lab_cost' => 200, 'sort_order' => 6],
            ],
        ],
        [
            'name_ar' => 'خطة ترميم شاملة',
            'name_en' => 'Full Mouth Restoration Plan',
            'category' => 'restorative',
            'description_ar' => 'ترميم كامل للأسنان المتضررة بالحشوات والتيجان',
            'description_en' => 'Complete restoration with fillings, root canals, and crowns',
            'estimated_sessions' => 8,
            'priority' => 'high',
            'treatments' => [
                ['treatment_type' => 'scaling', 'description' => 'Full mouth scaling and cleaning', 'cost' => 60, 'lab_cost' => 0, 'sort_order' => 1],
                ['treatment_type' => 'root_canal', 'description' => 'Root canal treatment', 'cost' => 250, 'lab_cost' => 0, 'sort_order' => 2],
                ['treatment_type' => 'crown', 'description' => 'Post-RCT crown', 'cost' => 350, 'lab_cost' => 200, 'sort_order' => 3],
                ['treatment_type' => 'filling', 'description' => 'Composite filling - Class II', 'cost' => 80, 'lab_cost' => 0, 'sort_order' => 4],
                ['treatment_type' => 'filling', 'description' => 'Composite filling - Class I', 'cost' => 60, 'lab_cost' => 0, 'sort_order' => 5],
            ],
        ],
        [
            'name_ar' => 'خطة علاج اللثة',
            'name_en' => 'Periodontal Treatment Plan',
            'category' => 'periodontal',
            'description_ar' => 'علاج أمراض اللثة من التنظيف العميق إلى المتابعة',
            'description_en' => 'Gum disease treatment from deep scaling to follow-up',
            'estimated_sessions' => 6,
            'priority' => 'high',
            'treatments' => [
                ['treatment_type' => 'scaling', 'description' => 'Deep scaling (upper right quadrant)', 'cost' => 80, 'lab_cost' => 0, 'sort_order' => 1],
                ['treatment_type' => 'scaling', 'description' => 'Deep scaling (upper left quadrant)', 'cost' => 80, 'lab_cost' => 0, 'sort_order' => 2],
                ['treatment_type' => 'scaling', 'description' => 'Deep scaling (lower right quadrant)', 'cost' => 80, 'lab_cost' => 0, 'sort_order' => 3],
                ['treatment_type' => 'scaling', 'description' => 'Deep scaling (lower left quadrant)', 'cost' => 80, 'lab_cost' => 0, 'sort_order' => 4],
                ['treatment_type' => 'gum_treatment', 'description' => 'Gum therapy / curettage', 'cost' => 200, 'lab_cost' => 0, 'sort_order' => 5],
            ],
        ],
        [
            'name_ar' => 'خطة جراحة الفك',
            'name_en' => 'Oral Surgery Plan',
            'category' => 'surgical',
            'description_ar' => 'خلع ضروس العقل المطمورة مع رفع الجيب الأنفي',
            'description_en' => 'Impacted wisdom teeth extraction with sinus lift',
            'estimated_sessions' => 4,
            'priority' => 'urgent',
            'treatments' => [
                ['treatment_type' => 'surgical_extraction', 'tooth_number' => 18, 'description' => 'Surgical extraction - upper right wisdom', 'cost' => 200, 'lab_cost' => 0, 'sort_order' => 1],
                ['treatment_type' => 'surgical_extraction', 'tooth_number' => 28, 'description' => 'Surgical extraction - upper left wisdom', 'cost' => 200, 'lab_cost' => 0, 'sort_order' => 2],
                ['treatment_type' => 'surgical_extraction', 'tooth_number' => 38, 'description' => 'Surgical extraction - lower left wisdom', 'cost' => 250, 'lab_cost' => 0, 'sort_order' => 3],
                ['treatment_type' => 'surgical_extraction', 'tooth_number' => 48, 'description' => 'Surgical extraction - lower right wisdom', 'cost' => 250, 'lab_cost' => 0, 'sort_order' => 4],
            ],
        ],
        [
            'name_ar' => 'خطة أسنان أطفال',
            'name_en' => 'Pediatric Dental Plan',
            'category' => 'pediatric',
            'description_ar' => 'علاج أسنان الأطفال مع الفلورايد والحشوات الوقائية',
            'description_en' => 'Child dental treatment with fluoride and sealants',
            'estimated_sessions' => 3,
            'priority' => 'normal',
            'treatments' => [
                ['treatment_type' => 'cleaning', 'description' => 'Child prophylaxis cleaning', 'cost' => 40, 'lab_cost' => 0, 'sort_order' => 1],
                ['treatment_type' => 'fluoride', 'description' => 'Fluoride varnish application', 'cost' => 30, 'lab_cost' => 10, 'sort_order' => 2],
                ['treatment_type' => 'sealant', 'tooth_number' => 16, 'description' => 'Pit & fissure sealant', 'cost' => 25, 'lab_cost' => 5, 'sort_order' => 3],
                ['treatment_type' => 'sealant', 'tooth_number' => 26, 'description' => 'Pit & fissure sealant', 'cost' => 25, 'lab_cost' => 5, 'sort_order' => 4],
                ['treatment_type' => 'sealant', 'tooth_number' => 36, 'description' => 'Pit & fissure sealant', 'cost' => 25, 'lab_cost' => 5, 'sort_order' => 5],
                ['treatment_type' => 'sealant', 'tooth_number' => 46, 'description' => 'Pit & fissure sealant', 'cost' => 25, 'lab_cost' => 5, 'sort_order' => 6],
            ],
        ],
        [
            'name_ar' => 'جسر أسنان ثابت',
            'name_en' => 'Fixed Dental Bridge Plan',
            'category' => 'restorative',
            'description_ar' => 'تركيب جسر ثابت لتعويض سن مفقود',
            'description_en' => 'Fixed bridge to replace a missing tooth',
            'estimated_sessions' => 3,
            'priority' => 'normal',
            'treatments' => [
                ['treatment_type' => 'crown', 'description' => 'Abutment tooth preparation (mesial)', 'cost' => 300, 'lab_cost' => 200, 'sort_order' => 1],
                ['treatment_type' => 'bridge', 'description' => 'Pontic (missing tooth replacement)', 'cost' => 350, 'lab_cost' => 250, 'sort_order' => 2],
                ['treatment_type' => 'crown', 'description' => 'Abutment tooth preparation (distal)', 'cost' => 300, 'lab_cost' => 200, 'sort_order' => 3],
            ],
        ],
        [
            'name_ar' => 'طقم أسنان متحرك',
            'name_en' => 'Removable Denture Plan',
            'category' => 'restorative',
            'description_ar' => 'تركيب طقم أسنان جزئي أو كامل',
            'description_en' => 'Partial or complete removable denture',
            'estimated_sessions' => 5,
            'priority' => 'normal',
            'treatments' => [
                ['treatment_type' => 'extraction', 'description' => 'Extract non-restorable teeth', 'cost' => 60, 'lab_cost' => 0, 'sort_order' => 1],
                ['treatment_type' => 'scaling', 'description' => 'Pre-denture cleaning', 'cost' => 50, 'lab_cost' => 0, 'sort_order' => 2],
                ['treatment_type' => 'denture', 'description' => 'Impression and bite registration', 'cost' => 100, 'lab_cost' => 50, 'sort_order' => 3],
                ['treatment_type' => 'denture', 'description' => 'Try-in and adjustments', 'cost' => 50, 'lab_cost' => 0, 'sort_order' => 4],
                ['treatment_type' => 'denture', 'description' => 'Final denture delivery', 'cost' => 300, 'lab_cost' => 250, 'sort_order' => 5],
            ],
        ],
        [
            'name_ar' => 'واقي أسنان ليلي',
            'name_en' => 'Night Guard Plan',
            'category' => 'general',
            'description_ar' => 'تصنيع وتركيب واقي أسنان للجز الليلي',
            'description_en' => 'Custom night guard for bruxism',
            'estimated_sessions' => 2,
            'priority' => 'low',
            'treatments' => [
                ['treatment_type' => 'night_guard', 'description' => 'Impression and fabrication', 'cost' => 150, 'lab_cost' => 100, 'sort_order' => 1],
                ['treatment_type' => 'night_guard', 'description' => 'Delivery and adjustments', 'cost' => 50, 'lab_cost' => 0, 'sort_order' => 2],
            ],
        ],
    ];

    /**
     * Seed default templates.
     */
    public static function seedDefaults(): void
    {
        foreach (self::DEFAULTS as $template) {
            $treatments = $template['treatments'];
            unset($template['treatments']);

            $totalCost = collect($treatments)->sum(fn ($t) => ($t['cost'] ?? 0) + ($t['lab_cost'] ?? 0));

            self::updateOrCreate(
                ['name_en' => $template['name_en']],
                array_merge($template, [
                    'treatments' => $treatments,
                    'estimated_cost' => $totalCost,
                ])
            );
        }
    }

    // ─── Relationships ──────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ─────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // ─── Helpers ────────────────────────────────────

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function getTreatmentsCount(): int
    {
        return count($this->treatments ?? []);
    }
}
