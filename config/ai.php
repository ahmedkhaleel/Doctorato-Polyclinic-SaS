<?php

/*
| AI layer config. Runtime values (key, models, budget) live in Settings and are
| editable from /admin/ai/settings — these are only fallback defaults.
*/
return [
    'provider' => env('AI_PROVIDER', 'openai'),

    'openai' => [
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'api_key' => env('OPENAI_API_KEY'), // fallback only; prefer the encrypted Setting
        'organization' => env('OPENAI_ORG'),
        'timeout' => (int) env('AI_TIMEOUT', 60),
    ],

    'defaults' => [
        'model' => 'gpt-4o-mini',
        'clinical_model' => 'gpt-4o',
        'vision_model' => 'gpt-4o',
        'embedding_model' => 'text-embedding-3-small',
        'transcribe_model' => 'whisper-1',
        'temperature' => 0.4,
        'max_tokens' => 1200,
    ],

    // USD per 1K tokens — used by AiCostMeter for the budget circuit-breaker.
    'pricing' => [
        'gpt-4o-mini' => ['in' => 0.00015, 'out' => 0.0006],
        'gpt-4o' => ['in' => 0.0025, 'out' => 0.01],
        'text-embedding-3-small' => ['in' => 0.00002, 'out' => 0],
        'whisper-1' => ['in' => 0.006, 'out' => 0], // per minute (approx)
    ],
];
