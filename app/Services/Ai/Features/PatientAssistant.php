<?php

namespace App\Services\Ai\Features;

use App\Models\AiConversation;
use App\Services\Ai\AiManager;
use App\Services\Ai\AiResult;

/**
 * Patient-facing assistant. Retrieves relevant clinic content (RAG) and answers
 * in the patient's language. Strictly non-diagnostic: it explains services,
 * hours and policies and guides toward booking — never gives medical advice.
 */
class PatientAssistant
{
    public function __construct(
        private readonly AiManager $ai,
        private readonly EmbeddingService $embeddings,
    ) {}

    public function ask(string $question, string $sessionId, array $options = []): AiResult
    {
        $messages = $this->buildMessages($question, $sessionId, $options);
        $result = $this->ai->generate('patient_assistant', $messages, $options);
        $this->store($question, $result->text, $sessionId, $options);

        return $result;
    }

    /** Streamed variant — emits each token via $onDelta, stores the turn at the end. */
    public function askStream(string $question, string $sessionId, callable $onDelta, array $options = []): AiResult
    {
        $messages = $this->buildMessages($question, $sessionId, $options);
        $result = $this->ai->stream('patient_assistant', $messages, $onDelta, $options);
        $this->store($question, $result->text, $sessionId, $options);

        return $result;
    }

    private function buildMessages(string $question, string $sessionId, array $options): array
    {
        $context = $this->embeddings->search($question, 4);
        $contextBlock = $context ? implode("\n- ", $context) : '';
        $system = $this->systemPrompt($options['locale'] ?? app()->getLocale(), $contextBlock);

        $history = AiConversation::where('session_id', $sessionId)
            ->latest()->take(6)->get()->reverse()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->values()->all();

        return array_merge(
            [['role' => 'system', 'content' => $system]],
            $history,
            [['role' => 'user', 'content' => $question]],
        );
    }

    private function store(string $question, string $answer, string $sessionId, array $options): void
    {
        AiConversation::create(['session_id' => $sessionId, 'role' => 'user', 'content' => $question, 'patient_id' => $options['patient_id'] ?? null]);
        AiConversation::create(['session_id' => $sessionId, 'role' => 'assistant', 'content' => $answer, 'patient_id' => $options['patient_id'] ?? null]);
    }

    private function systemPrompt(string $locale, string $context): string
    {
        $clinic = \App\Models\Setting::get('clinic_name', 'Doctorato Polyclinic');

        $base = $locale === 'ar'
            ? "أنت مساعد افتراضي لعيادة \"{$clinic}\". مهمتك مساعدة الزوار في معرفة الخدمات والأطباء والمواعيد وكيفية الحجز. "
                .'لا تقدّم تشخيصًا أو نصيحة طبية أبدًا؛ وجّه المريض لحجز موعد مع الطبيب المختص. أجب بإيجاز وباللغة العربية.'
            : "You are a virtual assistant for \"{$clinic}\" clinic. Help visitors learn about services, doctors, hours and how to book. "
                .'Never give a diagnosis or medical advice; guide the patient to book with the right specialist. Answer concisely in English.';

        if ($context !== '') {
            $label = $locale === 'ar' ? "\n\nمعلومات من العيادة لتستعين بها:\n- " : "\n\nClinic information to use:\n- ";
            $base .= $label.$context;
        }

        return $base;
    }
}
