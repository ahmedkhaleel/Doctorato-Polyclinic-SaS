<?php

namespace App\Services\Ai\Features;

use App\Models\AiPromptTemplate;
use App\Services\Ai\AiManager;
use App\Services\Ai\AiResult;

/**
 * Generic runner for Wave-1 text features (translation, comms drafting, SEO,
 * lead reply, campaign copy). Resolves the admin-editable prompt template for
 * the feature/locale (or a built-in default), fills {{placeholders}} from the
 * provided variables, and calls AiManager (which enforces the gate, PHI
 * redaction, cost logging). No PHI is required for any Wave-1 feature.
 */
class TextAssistant
{
    /** Built-in defaults used when no DB prompt template exists. */
    private const DEFAULTS = [
        'translation' => [
            'system' => 'You are a professional medical translator. Translate accurately, preserving medical terminology and tone.',
            'user' => "Translate the following text to {{target}}:\n\n{{text}}",
        ],
        'comms_drafting' => [
            'system' => 'You write professional, warm, concise clinic messages. Match the requested channel and language.',
            'user' => 'Write a {{channel}} message to a patient about: {{topic}}',
        ],
        'seo_content' => [
            'system' => 'You are a medical content writer skilled in SEO. Write clear, accurate, engaging content.',
            'user' => 'Write {{type}} about: {{topic}}. Make it SEO-friendly and well structured.',
        ],
        'lead_reply' => [
            'system' => 'You are a friendly clinic sales assistant. Reply to prospective patients persuasively and helpfully, never making medical claims.',
            'user' => "A prospective patient wrote:\n{{message}}\n\nWrite a {{tone}} reply.",
        ],
        'campaign_copy' => [
            'system' => 'You are a healthcare marketing copywriter. Write compliant, compelling campaign copy.',
            'user' => 'Write {{channel}} campaign copy for: {{product}}. Goal: {{goal}}.',
        ],
    ];

    public function __construct(private readonly AiManager $ai) {}

    /**
     * @param  array<string,string>  $vars
     */
    public function run(string $feature, array $vars, string $locale = 'ar', array $options = []): AiResult
    {
        [$system, $userTemplate] = $this->prompt($feature, $locale);

        $messages = [];
        if ($system !== '') {
            $messages[] = ['role' => 'system', 'content' => $system];
        }
        $messages[] = ['role' => 'user', 'content' => $this->fill($userTemplate, $vars)];

        return $this->ai->generate($feature, $messages, $options);
    }

    /** @return array{0:string,1:string} [system, userTemplate] */
    private function prompt(string $feature, string $locale): array
    {
        if ($tpl = AiPromptTemplate::resolve($feature, $locale)) {
            return [(string) $tpl->system_prompt, (string) ($tpl->user_template ?: '{{text}}')];
        }
        $d = self::DEFAULTS[$feature] ?? ['system' => '', 'user' => '{{text}}'];

        return [$d['system'], $d['user']];
    }

    private function fill(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{{'.$key.'}}', (string) $value, $template);
        }

        return $template;
    }
}
