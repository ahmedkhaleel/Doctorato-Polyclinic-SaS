<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Services\Ai\Exceptions\AiUnavailableException;
use App\Services\Ai\Features\PatientAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Patient-portal AI assistant (RAG). Non-diagnostic: answers about services,
 * doctors, hours and booking. Gated by the patient_assistant feature flag.
 */
class AiAssistantController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Patient/AiAssistant', [
            'enabled' => app(\App\Services\Ai\AiManager::class)->isReady()
                && \App\Models\AiFeatureFlag::isEnabled('patient_assistant'),
        ]);
    }

    public function ask(Request $request, PatientAssistant $assistant): JsonResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|max:1000',
            'session_id' => 'nullable|string|max:64',
        ]);

        $patient = $request->user('patient') ?? auth()->user();
        $session = $validated['session_id'] ?: ('patient-'.($patient->id ?? 'guest').'-'.Str::random(8));

        try {
            $result = $assistant->ask($validated['question'], $session, [
                'locale' => app()->getLocale(),
                'patient_id' => $patient->id ?? null,
                'rate_key' => 'patient:'.($patient->id ?? $request->ip()),
                'actor' => ['type' => 'patient', 'id' => $patient->id ?? null],
            ]);
        } catch (AiUnavailableException $e) {
            return response()->json([
                'ok' => false,
                'message' => app()->getLocale() === 'ar'
                    ? 'المساعد غير متاح حاليًا. يمكنك التواصل مع العيادة مباشرة.'
                    : 'The assistant is currently unavailable. Please contact the clinic directly.',
            ], 422);
        }

        return response()->json(['ok' => true, 'text' => $result->text, 'session_id' => $session]);
    }
}
