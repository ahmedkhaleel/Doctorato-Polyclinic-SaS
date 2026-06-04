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
        ];
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

    /** Total = sum of chosen answer values. Answers: [itemIndex => chosenValue]. */
    public static function score(string $key, array $answers): int
    {
        $def = self::definition($key);
        if (! $def) {
            return 0;
        }
        $valid = array_column($def['options'], 'v');

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
