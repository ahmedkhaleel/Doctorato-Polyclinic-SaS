<?php

namespace App\Console\Commands;

use App\Models\AiFeatureFlag;
use App\Models\Setting;
use App\Services\Ai\AiManager;
use App\Services\Ai\Features\EmbeddingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Rebuilds the patient-assistant RAG index so it never goes stale as FAQs,
 * services and doctor bios change. No-op unless AI + the patient_assistant
 * feature are enabled and the driver is configured (so it costs nothing when off).
 */
class AiRebuildEmbeddings extends Command
{
    protected $signature = 'ai:rebuild-embeddings';

    protected $description = 'Rebuild the patient-assistant RAG embeddings index (when AI + patient_assistant are enabled).';

    public function handle(AiManager $ai, EmbeddingService $embeddings): int
    {
        if (! Setting::get('ai_enabled', false) || ! AiFeatureFlag::isEnabled('patient_assistant') || ! $ai->isReady()) {
            $this->info('AI / patient assistant disabled — skipping embeddings rebuild.');

            return self::SUCCESS;
        }

        try {
            $count = $embeddings->rebuild();
            $this->info("Rebuilt RAG index: {$count} chunks.");
            Log::info("[ai.rebuild-embeddings] indexed {$count} chunks.");
        } catch (\Throwable $e) {
            Log::warning('[ai.rebuild-embeddings] failed: '.$e->getMessage());
            $this->error('Rebuild failed: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
