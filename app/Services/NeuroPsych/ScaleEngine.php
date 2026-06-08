<?php

namespace App\Services\NeuroPsych;

/**
 * NP2 — measurement-based-care scale engine. Instrument definitions and their
 * scoring/severity logic live HERE in code (not editable JSON in the DB) so the
 * clinically-critical scoring is testable and version-controlled. Only the
 * results are persisted (scale_results).
 *
 * Each instrument: items (questions, ar/en), an option set (value per choice),
 * a total = sum of chosen values, and severity bands. `flagItems` marks items
 * whose non-zero answer raises a clinical flag (e.g. PHQ-9 item 9 = suicidality).
 */
class ScaleEngine
{
    /**
     * @return array<string,array> instrument registry keyed by scale key
     */
    public static function registry(): array
    {
        // Standard 0–3 Likert used by PHQ-9 / GAD-7.
        $likert03 = [
            ['v' => 0, 'en' => 'Not at all', 'ar' => 'إطلاقًا'],
            ['v' => 1, 'en' => 'Several days', 'ar' => 'عدة أيام'],
            ['v' => 2, 'en' => 'More than half the days', 'ar' => 'أكثر من نصف الأيام'],
            ['v' => 3, 'en' => 'Nearly every day', 'ar' => 'كل يوم تقريبًا'],
        ];

        return [
            'phq9' => [
                'name_en' => 'PHQ-9 (Depression)',
                'name_ar' => 'PHQ-9 (الاكتئاب)',
                'module' => 'psychiatry',
                'options' => $likert03,
                'items' => array_map(fn ($en, $ar) => ['en' => $en, 'ar' => $ar], [
                    'Little interest or pleasure in doing things',
                    'Feeling down, depressed, or hopeless',
                    'Trouble falling/staying asleep, or sleeping too much',
                    'Feeling tired or having little energy',
                    'Poor appetite or overeating',
                    'Feeling bad about yourself',
                    'Trouble concentrating',
                    'Moving/speaking slowly or being fidgety/restless',
                    'Thoughts that you would be better off dead or of hurting yourself',
                ], [
                    'قلة الاهتمام أو المتعة في فعل الأشياء',
                    'الشعور بالإحباط أو الاكتئاب أو اليأس',
                    'صعوبة النوم أو النوم الزائد',
                    'الشعور بالتعب أو قلة الطاقة',
                    'ضعف الشهية أو الإفراط في الأكل',
                    'الشعور بالسوء تجاه نفسك',
                    'صعوبة التركيز',
                    'بطء الحركة/الكلام أو التهيّج',
                    'أفكار بأنك أفضل لو كنت ميتًا أو إيذاء نفسك',
                ]),
                'bands' => [
                    [0, 4, 'Minimal', 'ضئيل'],
                    [5, 9, 'Mild', 'خفيف'],
                    [10, 14, 'Moderate', 'متوسط'],
                    [15, 19, 'Moderately severe', 'متوسط-شديد'],
                    [20, 27, 'Severe', 'شديد'],
                ],
                'flagItems' => [8], // 0-based item 9 → suicidality
            ],
            'gad7' => [
                'name_en' => 'GAD-7 (Anxiety)',
                'name_ar' => 'GAD-7 (القلق)',
                'module' => 'psychiatry',
                'options' => $likert03,
                'items' => array_map(fn ($en, $ar) => ['en' => $en, 'ar' => $ar], [
                    'Feeling nervous, anxious, or on edge',
                    'Not being able to stop or control worrying',
                    'Worrying too much about different things',
                    'Trouble relaxing',
                    'Being so restless that it is hard to sit still',
                    'Becoming easily annoyed or irritable',
                    'Feeling afraid as if something awful might happen',
                ], [
                    'الشعور بالعصبية أو القلق أو التوتر',
                    'عدم القدرة على إيقاف القلق أو التحكّم فيه',
                    'القلق الزائد حول أمور مختلفة',
                    'صعوبة الاسترخاء',
                    'التململ لدرجة صعوبة الجلوس',
                    'الانزعاج أو التهيّج بسهولة',
                    'الخوف كأن شيئًا فظيعًا قد يحدث',
                ]),
                'bands' => [
                    [0, 4, 'Minimal', 'ضئيل'],
                    [5, 9, 'Mild', 'خفيف'],
                    [10, 14, 'Moderate', 'متوسط'],
                    [15, 21, 'Severe', 'شديد'],
                ],
                'flagItems' => [],
            ],
            'hit6' => [
                'name_en' => 'HIT-6 (Headache Impact)',
                'name_ar' => 'HIT-6 (أثر الصداع)',
                'module' => 'neurology',
                'options' => [
                    ['v' => 6, 'en' => 'Never', 'ar' => 'أبدًا'],
                    ['v' => 8, 'en' => 'Rarely', 'ar' => 'نادرًا'],
                    ['v' => 10, 'en' => 'Sometimes', 'ar' => 'أحيانًا'],
                    ['v' => 11, 'en' => 'Very often', 'ar' => 'غالبًا'],
                    ['v' => 13, 'en' => 'Always', 'ar' => 'دائمًا'],
                ],
                'items' => array_map(fn ($en, $ar) => ['en' => $en, 'ar' => $ar], [
                    'Severe pain from headaches',
                    'Headaches limit daily activities',
                    'Wishing to lie down due to headaches',
                    'Too tired to work/play due to headaches',
                    'Felt fed up or irritated due to headaches',
                    'Headaches limited concentration',
                ], [
                    'ألم شديد من الصداع',
                    'الصداع يحدّ من الأنشطة اليومية',
                    'الرغبة في الاستلقاء بسبب الصداع',
                    'التعب الشديد عن العمل/اللعب بسبب الصداع',
                    'الشعور بالضيق أو الانزعاج بسبب الصداع',
                    'الصداع حدّ من التركيز',
                ]),
                'bands' => [
                    [0, 49, 'Little/no impact', 'أثر ضئيل'],
                    [50, 55, 'Some impact', 'بعض الأثر'],
                    [56, 59, 'Substantial impact', 'أثر كبير'],
                    [60, 78, 'Severe impact', 'أثر شديد'],
                ],
                'flagItems' => [],
            ],

            // ── Physiotherapy PROMs (PT-7) ───────────────────────────
            // ODI & NDI: 10 sections × 0–5 → raw 0–50 (= % disability ×2);
            // higher = worse. LEFS: 20 items × 0–4 → 0–80; higher = better.
            'odi' => [
                'name_en' => 'ODI (Oswestry — Low Back)',
                'name_ar' => 'مؤشر أوسويستري لعجز أسفل الظهر',
                'module' => 'physiotherapy',
                'higher_is_better' => false,
                'mcid' => 10, // points (raw); ≈10% change
                'options' => $disability05 = [
                    ['v' => 0, 'en' => 'No limitation', 'ar' => 'بدون قيود'],
                    ['v' => 1, 'en' => 'Very mild', 'ar' => 'بسيط جدًا'],
                    ['v' => 2, 'en' => 'Mild', 'ar' => 'بسيط'],
                    ['v' => 3, 'en' => 'Moderate', 'ar' => 'متوسط'],
                    ['v' => 4, 'en' => 'Severe', 'ar' => 'شديد'],
                    ['v' => 5, 'en' => 'Unable / worst', 'ar' => 'غير قادر / الأسوأ'],
                ],
                'items' => array_map(fn ($en, $ar) => ['en' => $en, 'ar' => $ar], [
                    'Pain intensity', 'Personal care (washing, dressing)', 'Lifting', 'Walking',
                    'Sitting', 'Standing', 'Sleeping', 'Sex life', 'Social life', 'Travelling',
                ], [
                    'شدة الألم', 'العناية الشخصية (الاغتسال، اللبس)', 'الرفع', 'المشي',
                    'الجلوس', 'الوقوف', 'النوم', 'الحياة الزوجية', 'الحياة الاجتماعية', 'السفر',
                ]),
                'bands' => [
                    [0, 10, 'Minimal disability', 'عجز ضئيل'],
                    [11, 20, 'Moderate disability', 'عجز متوسط'],
                    [21, 30, 'Severe disability', 'عجز شديد'],
                    [31, 40, 'Crippled', 'عجز بالغ'],
                    [41, 50, 'Bed-bound', 'طريح الفراش'],
                ],
                'flagItems' => [],
            ],
            'ndi' => [
                'name_en' => 'NDI (Neck Disability)',
                'name_ar' => 'مؤشر عجز الرقبة',
                'module' => 'physiotherapy',
                'higher_is_better' => false,
                'mcid' => 7, // ≈7.5 points
                'options' => $disability05,
                'items' => array_map(fn ($en, $ar) => ['en' => $en, 'ar' => $ar], [
                    'Pain intensity', 'Personal care', 'Lifting', 'Reading', 'Headaches',
                    'Concentration', 'Work', 'Driving', 'Sleeping', 'Recreation',
                ], [
                    'شدة الألم', 'العناية الشخصية', 'الرفع', 'القراءة', 'الصداع',
                    'التركيز', 'العمل', 'القيادة', 'النوم', 'الترفيه',
                ]),
                'bands' => [
                    [0, 4, 'No disability', 'بدون عجز'],
                    [5, 14, 'Mild disability', 'عجز خفيف'],
                    [15, 24, 'Moderate disability', 'عجز متوسط'],
                    [25, 34, 'Severe disability', 'عجز شديد'],
                    [35, 50, 'Complete disability', 'عجز كامل'],
                ],
                'flagItems' => [],
            ],
            'lefs' => [
                'name_en' => 'LEFS (Lower Extremity Function)',
                'name_ar' => 'مقياس وظيفة الطرف السفلي',
                'module' => 'physiotherapy',
                'higher_is_better' => true,
                'mcid' => 9,
                'options' => [
                    ['v' => 0, 'en' => 'Extreme difficulty / unable', 'ar' => 'صعوبة بالغة / غير قادر'],
                    ['v' => 1, 'en' => 'Quite a bit of difficulty', 'ar' => 'صعوبة كبيرة'],
                    ['v' => 2, 'en' => 'Moderate difficulty', 'ar' => 'صعوبة متوسطة'],
                    ['v' => 3, 'en' => 'A little bit of difficulty', 'ar' => 'صعوبة بسيطة'],
                    ['v' => 4, 'en' => 'No difficulty', 'ar' => 'بدون صعوبة'],
                ],
                'items' => array_map(fn ($en, $ar) => ['en' => $en, 'ar' => $ar], [
                    'Any usual work/housework/school', 'Usual hobbies/recreation/sports', 'Getting in/out of bath',
                    'Walking between rooms', 'Putting on shoes/socks', 'Squatting', 'Lifting an object from the floor',
                    'Light activities at home', 'Heavy activities at home', 'Getting in/out of a car',
                    'Walking 2 blocks', 'Walking a mile', 'Going up/down 10 stairs', 'Standing for 1 hour',
                    'Sitting for 1 hour', 'Running on even ground', 'Running on uneven ground',
                    'Making sharp turns while running', 'Hopping', 'Rolling over in bed',
                ], [
                    'العمل/الأعمال المنزلية/الدراسة المعتادة', 'الهوايات/الترفيه/الرياضة المعتادة', 'الدخول/الخروج من الحمام',
                    'المشي بين الغرف', 'ارتداء الحذاء/الجوارب', 'القرفصاء', 'رفع جسم من الأرض',
                    'الأنشطة الخفيفة بالمنزل', 'الأنشطة الثقيلة بالمنزل', 'الدخول/الخروج من السيارة',
                    'المشي مسافة قصيرة', 'المشي مسافة طويلة', 'صعود/نزول 10 درجات', 'الوقوف لمدة ساعة',
                    'الجلوس لمدة ساعة', 'الجري على أرض مستوية', 'الجري على أرض غير مستوية',
                    'الانعطاف الحاد أثناء الجري', 'القفز', 'التقلّب في الفراش',
                ]),
                'bands' => [
                    [0, 20, 'Severe limitation', 'قصور شديد'],
                    [21, 40, 'Moderate limitation', 'قصور متوسط'],
                    [41, 60, 'Mild limitation', 'قصور خفيف'],
                    [61, 80, 'Minimal / full function', 'وظيفة شبه كاملة'],
                ],
                'flagItems' => [],
            ],
            'quickdash' => [
                'name_en' => 'QuickDASH (Arm/Shoulder/Hand)',
                'name_ar' => 'كويك-داش (الذراع والكتف واليد)',
                'module' => 'physiotherapy',
                'higher_is_better' => false,
                'mcid' => 11,
                'score_mode' => 'mean_scaled', // ((mean − 1)/4) × 100, range 0–100
                'options' => [
                    ['v' => 1, 'en' => 'No difficulty', 'ar' => 'بدون صعوبة'],
                    ['v' => 2, 'en' => 'Mild difficulty', 'ar' => 'صعوبة بسيطة'],
                    ['v' => 3, 'en' => 'Moderate difficulty', 'ar' => 'صعوبة متوسطة'],
                    ['v' => 4, 'en' => 'Severe difficulty', 'ar' => 'صعوبة شديدة'],
                    ['v' => 5, 'en' => 'Unable', 'ar' => 'غير قادر'],
                ],
                'items' => array_map(fn ($en, $ar) => ['en' => $en, 'ar' => $ar], [
                    'Open a tight or new jar', 'Do heavy household chores', 'Carry a shopping bag or briefcase',
                    'Wash your back', 'Use a knife to cut food', 'Recreational activities with arm force/impact',
                    'Arm/shoulder/hand problem limited social activities', 'Limited work or daily activities',
                    'Arm/shoulder/hand pain', 'Tingling (pins and needles)', 'Difficulty sleeping due to pain',
                ], [
                    'فتح برطمان محكم أو جديد', 'القيام بالأعمال المنزلية الثقيلة', 'حمل كيس تسوق أو حقيبة',
                    'غسل ظهرك', 'استخدام السكين لتقطيع الطعام', 'أنشطة ترفيهية تتطلب قوة الذراع',
                    'مشكلة الذراع حدّت من الأنشطة الاجتماعية', 'محدودية العمل أو الأنشطة اليومية',
                    'ألم الذراع/الكتف/اليد', 'تنميل (وخز)', 'صعوبة النوم بسبب الألم',
                ]),
                'bands' => [
                    [0, 14, 'Minimal disability', 'عجز ضئيل'],
                    [15, 40, 'Mild disability', 'عجز خفيف'],
                    [41, 60, 'Moderate disability', 'عجز متوسط'],
                    [61, 100, 'Severe disability', 'عجز شديد'],
                ],
                'flagItems' => [],
            ],
        ];
    }

    /** Minimal clinically important difference (points) for a scale, if defined. */
    public static function mcid(string $key): ?int
    {
        return self::definition($key)['mcid'] ?? null;
    }

    /** Whether a higher score is the better (improving) direction. Default: false. */
    public static function higherIsBetter(string $key): bool
    {
        return (bool) (self::definition($key)['higher_is_better'] ?? false);
    }

    public static function exists(string $key): bool
    {
        return isset(self::registry()[$key]);
    }

    public static function definition(string $key): ?array
    {
        return self::registry()[$key] ?? null;
    }

    /** Scales available for a module (module-specific only). */
    public static function forModule(string $module): array
    {
        return array_filter(self::registry(), fn ($d) => $d['module'] === $module);
    }

    /**
     * Score a completed scale. Answers: [itemIndex => chosenValue].
     *
     * Default mode is 'sum' (PHQ-9, GAD-7, ODI, …). Scales that declare
     * 'score_mode' => 'mean_scaled' (e.g. QuickDASH) are scored as the mean of
     * the answered items rescaled to 0–100 across the option min/max, i.e.
     * ((mean − min) / (max − min)) × 100 — matching the published instrument.
     */
    public static function score(string $key, array $answers): int
    {
        $def = self::definition($key);
        if (! $def) {
            return 0;
        }
        $valid = array_column($def['options'], 'v');

        if (($def['score_mode'] ?? 'sum') === 'mean_scaled') {
            $vals = array_values(array_filter(
                array_map('intval', $answers),
                fn ($v) => in_array($v, $valid, true)
            ));
            if (count($vals) === 0) {
                return 0;
            }
            $mean = array_sum($vals) / count($vals);
            $min = min($valid);
            $max = max($valid);

            return (int) round(($mean - $min) / max(1, $max - $min) * 100);
        }

        $total = 0;
        foreach ($answers as $val) {
            $v = (int) $val;
            if (in_array($v, $valid, true)) {
                $total += $v;
            }
        }

        return $total;
    }

    /** Severity band label for a score: ['en'=>…, 'ar'=>…] or null. */
    public static function severity(string $key, int $score): ?array
    {
        $def = self::definition($key);
        if (! $def) {
            return null;
        }
        foreach ($def['bands'] as [$min, $max, $en, $ar]) {
            if ($score >= $min && $score <= $max) {
                return ['en' => $en, 'ar' => $ar];
            }
        }

        return null;
    }

    /** True if any flag-item (e.g. PHQ-9 suicidality) has a non-zero answer. */
    public static function raisesFlag(string $key, array $answers): bool
    {
        $def = self::definition($key);
        if (! $def) {
            return false;
        }
        foreach ($def['flagItems'] as $idx) {
            if ((int) ($answers[$idx] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }
}
