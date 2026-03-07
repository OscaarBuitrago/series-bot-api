<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TmdbService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.themoviedb.org/3';

    public function __construct()
    {
        $this->apiKey = config('services.tmdb.key');
    }

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withToken($this->apiKey)->acceptJson();
    }

    public function search(string $query, string $type = 'multi'): array
    {
        $response = $this->http()->get("{$this->baseUrl}/search/{$type}", [
            'query' => $query,
            'language' => 'es-ES',
        ]);

        if ($response->failed()) {
            return [];
        }

        return collect($response->json('results', []))
            ->take(10)
            ->map(fn($item) => [
                'tmdb_id' => $item['id'],
                'name' => $item['name'] ?? $item['title'] ?? '',
                'type' => $item['media_type'] ?? $type,
                'overview' => $item['overview'] ?? '',
                'poster_path' => $item['poster_path'] ?? null,
                'first_air_date' => $item['first_air_date'] ?? $item['release_date'] ?? null,
            ])
            ->toArray();
    }

    public function getDetails(int $tmdbId, string $type = 'tv'): array
    {
        $response = $this->http()->get("{$this->baseUrl}/{$type}/{$tmdbId}", [
            'language' => 'es-ES',
            'append_to_response' => 'seasons',
        ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json();
    }

    public function getSeasonDetails(int $tmdbId, int $season): array
    {
        $response = $this->http()->get("{$this->baseUrl}/tv/{$tmdbId}/season/{$season}", [
            'language' => 'es-ES',
        ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json();
    }
}
