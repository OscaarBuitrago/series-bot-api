<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PineconeService
{
    private string $apiKey;
    private string $indexUrl;
    private string $embeddingModel = 'text-embedding-3-small';
    private string $openAiKey;

    public function __construct()
    {
        $this->apiKey = config('services.pinecone.key');
        $this->indexUrl = config('services.pinecone.index_url');
        $this->openAiKey = config('services.openai.key', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->indexUrl) && !empty($this->apiKey);
    }

    public function query(string $text, array $filter = [], int $topK = 5): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $embedding = $this->embed($text);
        if (empty($embedding)) {
            return [];
        }

        $payload = [
            'vector' => $embedding,
            'topK' => $topK,
            'includeMetadata' => true,
        ];

        if (!empty($filter)) {
            $payload['filter'] = $filter;
        }

        $response = Http::withHeaders([
            'Api-Key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->indexUrl}/query", $payload);

        if ($response->failed()) {
            return [];
        }

        return collect($response->json('matches', []))
            ->map(fn($match) => $match['metadata']['text'] ?? '')
            ->filter()
            ->values()
            ->toArray();
    }

    public function upsert(string $id, string $text, array $metadata = []): bool
    {
        $embedding = $this->embed($text);
        if (empty($embedding)) {
            return false;
        }

        $metadata['text'] = $text;

        $response = Http::withHeaders([
            'Api-Key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->indexUrl}/vectors/upsert", [
            'vectors' => [[
                'id' => $id,
                'values' => $embedding,
                'metadata' => $metadata,
            ]],
        ]);

        return $response->successful();
    }

    public function buildSeriesFilter(int $tmdbId, int $maxSeason, int $maxEpisode): array
    {
        return [
            'tmdb_id' => ['$eq' => $tmdbId],
            'season' => ['$lte' => $maxSeason],
        ];
    }

    private function embed(string $text): array
    {
        if (empty($this->openAiKey)) {
            return [];
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->openAiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/embeddings', [
            'input' => $text,
            'model' => $this->embeddingModel,
        ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json('data.0.embedding', []);
    }
}
