<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Ai\Exceptions\AiUnavailableException;
use App\Services\Ai\Features\TextAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiAssistController extends Controller
{
    /** Wave-1 text features and their required variables. */
    private const FEATURES = [
        'translation' => ['text', 'target'],
        'comms_drafting' => ['channel', 'topic'],
        'seo_content' => ['type', 'topic'],
        'lead_reply' => ['message', 'tone'],
        'campaign_copy' => ['product', 'channel', 'goal'],
        // Wave 2 text features
        'satisfaction_sentiment' => ['feedback'],
        'followup_message' => ['context', 'channel'],
        'doctor_review_reply' => ['review', 'tone'],
        'doctor_bio' => ['name', 'specialty', 'highlights'],
    ];

    public function workspace(): Response
    {
        return Inertia::render('Admin/Ai/Assistant', [
            'features' => array_keys(self::FEATURES),
        ]);
    }

    public function generate(Request $request, TextAssistant $assistant): JsonResponse
    {
        $validated = $request->validate([
            'feature' => 'required|string|in:'.implode(',', array_keys(self::FEATURES)),
            'vars' => 'required|array',
            'locale' => 'nullable|string|in:ar,en',
        ]);

        $feature = $validated['feature'];
        $vars = [];
        foreach (self::FEATURES[$feature] as $key) {
            $vars[$key] = (string) ($validated['vars'][$key] ?? '');
        }

        try {
            $result = $assistant->run($feature, $vars, $validated['locale'] ?? app()->getLocale(), [
                'rate_key' => 'user:'.$request->user()?->id,
                'actor' => ['type' => 'user', 'id' => $request->user()?->id],
            ]);
        } catch (AiUnavailableException $e) {
            return response()->json([
                'ok' => false,
                'reason' => $e->reason,
                'message' => $this->reasonMessage($e->reason),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'reason' => 'error', 'message' => $e->getMessage()], 500);
        }

        return response()->json([
            'ok' => true,
            'text' => $result->text,
            'model' => $result->model,
            'tokens' => $result->totalTokens(),
        ]);
    }

    private function reasonMessage(string $reason): string
    {
        return match ($reason) {
            'disabled' => 'الذكاء الاصطناعي معطّل من الإعدادات. / AI is disabled in settings.',
            'feature_off' => 'هذه الميزة معطّلة. فعّلها من صفحة الميزات. / This feature is disabled.',
            'over_budget' => 'تم بلوغ ميزانية الذكاء الاصطناعي الشهرية. / Monthly AI budget reached.',
            'rate_limited' => 'طلبات كثيرة، يرجى الإبطاء قليلًا. / Too many requests, slow down.',
            'no_key' => 'مفتاح OpenAI غير مُهيّأ. / OpenAI key not configured.',
            default => 'الذكاء الاصطناعي غير متاح حاليًا. / AI is currently unavailable.',
        };
    }
}
