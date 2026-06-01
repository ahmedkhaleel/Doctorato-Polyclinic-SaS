<?php

namespace App\Services\Ai\Features;

use App\Models\AiEmbedding;
use App\Models\Setting;
use App\Services\Ai\AiManager;
use Illuminate\Support\Facades\DB;

/**
 * Builds and searches the RAG vector index over clinic-wide content (FAQs,
 * services, doctor bios). Cosine similarity is computed in PHP — the corpus is
 * small, which suits shared MySQL hosting (no pgvector).
 */
class EmbeddingService
{
    public function __construct(private readonly AiManager $ai) {}

    /** Rebuild the whole index. Returns the number of embedded chunks. */
    public function rebuild(): int
    {
        $chunks = $this->collectChunks();
        AiEmbedding::query()->delete();

        $model = Setting::get('ai_embedding_model', config('ai.defaults.embedding_model'));
        $count = 0;

        foreach (array_chunk($chunks, 50) as $batch) {
            $vectors = $this->ai->embed(array_column($batch, 'content'), ['model' => $model]);
            foreach ($batch as $i => $chunk) {
                if (! isset($vectors[$i])) {
                    continue;
                }
                AiEmbedding::create([
                    'source' => $chunk['source'],
                    'owner_type' => $chunk['owner_type'] ?? null,
                    'owner_id' => $chunk['owner_id'] ?? null,
                    'locale' => $chunk['locale'] ?? 'ar',
                    'content' => $chunk['content'],
                    'vector' => $vectors[$i],
                    'model' => $model,
                ]);
                $count++;
            }
        }

        return $count;
    }

    /** Return the top-k most relevant content snippets for a query. */
    public function search(string $query, int $k = 4): array
    {
        $rows = AiEmbedding::all(['content', 'vector']);
        if ($rows->isEmpty()) {
            return [];
        }

        $qVec = $this->ai->embed($query)[0] ?? null;
        if (! $qVec) {
            return [];
        }

        return $rows
            ->map(fn ($r) => ['content' => $r->content, 'score' => $this->cosine($qVec, $r->vector ?? [])])
            ->sortByDesc('score')
            ->take($k)
            ->pluck('content')
            ->values()
            ->all();
    }

    public function indexedCount(): int
    {
        return AiEmbedding::count();
    }

    private function cosine(array $a, array $b): float
    {
        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;
        $len = min(count($a), count($b));
        for ($i = 0; $i < $len; $i++) {
            $dot += $a[$i] * $b[$i];
            $na += $a[$i] * $a[$i];
            $nb += $b[$i] * $b[$i];
        }

        return ($na > 0 && $nb > 0) ? $dot / (sqrt($na) * sqrt($nb)) : 0.0;
    }

    /** @return array<int,array{source:string,content:string,owner_type?:string,owner_id?:int,locale?:string}> */
    private function collectChunks(): array
    {
        $chunks = [];

        if (DB::getSchemaBuilder()->hasTable('faqs')) {
            foreach (DB::table('faqs')->get() as $f) {
                $q = $f->question_ar ?? $f->question ?? '';
                $a = $f->answer_ar ?? $f->answer ?? '';
                if ($q || $a) {
                    $chunks[] = ['source' => 'faq', 'owner_type' => 'faq', 'owner_id' => $f->id, 'content' => trim($q.' — '.$a)];
                }
            }
        }

        if (DB::getSchemaBuilder()->hasTable('services')) {
            foreach (DB::table('services')->get() as $s) {
                $name = $s->name_ar ?? $s->name_en ?? '';
                $desc = $s->description_ar ?? $s->description_en ?? '';
                if ($name) {
                    $chunks[] = ['source' => 'service', 'owner_type' => 'service', 'owner_id' => $s->id, 'content' => trim($name.'. '.strip_tags((string) $desc))];
                }
            }
        }

        if (DB::getSchemaBuilder()->hasTable('doctors')) {
            foreach (DB::table('doctors')->get() as $d) {
                $name = $d->name_ar ?? $d->name_en ?? $d->name ?? '';
                $bio = $d->bio_ar ?? $d->bio_en ?? $d->bio ?? '';
                if ($name) {
                    $chunks[] = ['source' => 'doctor', 'owner_type' => 'doctor', 'owner_id' => $d->id, 'content' => trim('د. '.$name.'. '.strip_tags((string) $bio))];
                }
            }
        }

        return $chunks;
    }
}
