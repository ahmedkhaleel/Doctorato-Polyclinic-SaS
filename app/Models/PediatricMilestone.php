<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class PediatricMilestone extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Standard developmental milestones.
     */
    const MILESTONES = [
        // Gross Motor
        ['key' => 'lifts_head', 'category' => 'gross_motor', 'name_en' => 'Lifts head', 'name_ar' => 'يرفع رأسه', 'expected_months' => 2],
        ['key' => 'rolls_over', 'category' => 'gross_motor', 'name_en' => 'Rolls over', 'name_ar' => 'يتدحرج', 'expected_months' => 4],
        ['key' => 'sits_with_support', 'category' => 'gross_motor', 'name_en' => 'Sits with support', 'name_ar' => 'يجلس بدعم', 'expected_months' => 6],
        ['key' => 'sits_alone', 'category' => 'gross_motor', 'name_en' => 'Sits without support', 'name_ar' => 'يجلس بدون دعم', 'expected_months' => 9],
        ['key' => 'stands_with_help', 'category' => 'gross_motor', 'name_en' => 'Stands with help', 'name_ar' => 'يقف بمساعدة', 'expected_months' => 12],
        ['key' => 'walks', 'category' => 'gross_motor', 'name_en' => 'Walks independently', 'name_ar' => 'يمشي', 'expected_months' => 15],
        ['key' => 'runs', 'category' => 'gross_motor', 'name_en' => 'Runs', 'name_ar' => 'يركض', 'expected_months' => 18],
        ['key' => 'climbs_stairs', 'category' => 'gross_motor', 'name_en' => 'Climbs stairs', 'name_ar' => 'يصعد الدرج', 'expected_months' => 24],
        ['key' => 'jumps_both_feet', 'category' => 'gross_motor', 'name_en' => 'Jumps with both feet', 'name_ar' => 'يقفز بقدمين', 'expected_months' => 36],

        // Fine Motor
        ['key' => 'grasps_objects', 'category' => 'fine_motor', 'name_en' => 'Grasps objects', 'name_ar' => 'يمسك الأشياء', 'expected_months' => 3],
        ['key' => 'transfers_objects', 'category' => 'fine_motor', 'name_en' => 'Transfers hand to hand', 'name_ar' => 'ينقل من يد ليد', 'expected_months' => 6],
        ['key' => 'pincer_grasp', 'category' => 'fine_motor', 'name_en' => 'Pincer grasp', 'name_ar' => 'يلتقط بإبهام وسبابة', 'expected_months' => 9],
        ['key' => 'stacks_blocks', 'category' => 'fine_motor', 'name_en' => 'Stacks blocks', 'name_ar' => 'يضع مكعبات فوق بعض', 'expected_months' => 12],
        ['key' => 'scribbles', 'category' => 'fine_motor', 'name_en' => 'Scribbles', 'name_ar' => 'يخربش', 'expected_months' => 18],
        ['key' => 'draws_line', 'category' => 'fine_motor', 'name_en' => 'Draws vertical line', 'name_ar' => 'يرسم خط عمودي', 'expected_months' => 24],
        ['key' => 'draws_circle', 'category' => 'fine_motor', 'name_en' => 'Draws circle', 'name_ar' => 'يرسم دائرة', 'expected_months' => 36],

        // Language
        ['key' => 'cooing', 'category' => 'language', 'name_en' => 'Cooing sounds', 'name_ar' => 'أصوات (كوو)', 'expected_months' => 2],
        ['key' => 'babbling', 'category' => 'language', 'name_en' => 'Babbling', 'name_ar' => 'مناغاة', 'expected_months' => 6],
        ['key' => 'mama_dada', 'category' => 'language', 'name_en' => 'Says mama/dada', 'name_ar' => 'يقول ماما/بابا', 'expected_months' => 9],
        ['key' => 'first_words', 'category' => 'language', 'name_en' => '1-3 clear words', 'name_ar' => '1-3 كلمات واضحة', 'expected_months' => 12],
        ['key' => 'ten_words', 'category' => 'language', 'name_en' => '10-20 words', 'name_ar' => '10-20 كلمة', 'expected_months' => 18],
        ['key' => 'two_word_phrases', 'category' => 'language', 'name_en' => 'Two-word phrases', 'name_ar' => 'جمل من كلمتين', 'expected_months' => 24],
        ['key' => 'full_sentences', 'category' => 'language', 'name_en' => 'Full sentences', 'name_ar' => 'جمل كاملة', 'expected_months' => 36],

        // Social/Emotional
        ['key' => 'social_smile', 'category' => 'social', 'name_en' => 'Social smile', 'name_ar' => 'يبتسم اجتماعياً', 'expected_months' => 2],
        ['key' => 'stranger_anxiety', 'category' => 'social', 'name_en' => 'Recognizes strangers', 'name_ar' => 'يعرف الغرباء', 'expected_months' => 6],
        ['key' => 'separation_anxiety', 'category' => 'social', 'name_en' => 'Separation anxiety', 'name_ar' => 'قلق الانفصال', 'expected_months' => 9],
        ['key' => 'waves_bye', 'category' => 'social', 'name_en' => 'Waves bye', 'name_ar' => 'يلوح باي', 'expected_months' => 12],
        ['key' => 'parallel_play', 'category' => 'social', 'name_en' => 'Parallel play', 'name_ar' => 'يلعب بجانب الأطفال', 'expected_months' => 18],
        ['key' => 'pretend_play', 'category' => 'social', 'name_en' => 'Pretend play', 'name_ar' => 'يلعب تمثيلي', 'expected_months' => 24],
        ['key' => 'cooperative_play', 'category' => 'social', 'name_en' => 'Plays with others', 'name_ar' => 'يلعب مع الأطفال', 'expected_months' => 36],
    ];

    protected $fillable = [
        'patient_id', 'visit_id', 'doctor_id', 'assessment_date', 'age_months',
        'category', 'milestone_key', 'milestone_name_en', 'milestone_name_ar',
        'expected_age', 'status', 'achieved_date', 'notes',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'achieved_date' => 'date',
        'age_months' => 'decimal:2',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
