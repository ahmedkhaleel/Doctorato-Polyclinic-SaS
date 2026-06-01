<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Ai\Exceptions\AiUnavailableException;
use App\Services\Ai\Features\EmbeddingService;
use App\Services\Ai\Features\PatientAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Admin playground to build the RAG index and test the patient assistant. */
class AiPatientAssistantController extends Controller
{
    public function index(EmbeddingService $embeddings): Response
    {
        return Inertia::render('Admin/Ai/PatientAssistant', [
            'indexedCount' => $embeddings->indexedCount(),
        ]);
    }

    public function rebuild(EmbeddingService $embeddings): RedirectResponse
    {
        try {
            $count = $embeddings->rebuild();
        } catch (AiUnavailableException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Indexed :n items', ['n' => $count]) ?: "Indexed {$count} items.");
    }

    public function test(Request $request, PatientAssistant $assistant): JsonResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|max:1000',
            'session_id' => 'nullable|string|max:64',
        ]);

        try {
            $result = $assistant->ask(
                $validated['question'],
                $validated['session_id'] ?? 'admin-playground',
                ['locale' => app()->getLocale(), 'actor' => ['type' => 'user', 'id' => $request->user()?->id]],
            );
        } catch (AiUnavailableException $e) {
            return response()->json(['ok' => false, 'reason' => $e->reason, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'text' => $result->text, 'tokens' => $result->totalTokens()]);
    }
}
