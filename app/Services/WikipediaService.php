<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WikipediaService
{
    private string $baseUrl = 'https://en.wikipedia.org/api/rest_v1';
    private string $apiUrl = 'https://en.wikipedia.org/w/api.php';

    public function getSummary(string $seriesName, int $season): string
    {
        $searchTitle = $this->buildSearchTitle($seriesName, $season);
        $pageTitle = $this->findPage($searchTitle);

        if (!$pageTitle) {
            $pageTitle = $this->findPage("{$seriesName} TV series");
        }

        if (!$pageTitle) {
            return '';
        }

        $response = Http::get("{$this->baseUrl}/page/summary/" . urlencode($pageTitle));

        if ($response->failed()) {
            return '';
        }

        return $response->json('extract', '');
    }

    private function buildSearchTitle(string $seriesName, int $season): string
    {
        return "{$seriesName} season {$season}";
    }

    private function findPage(string $query): ?string
    {
        $response = Http::get($this->apiUrl, [
            'action' => 'query',
            'list' => 'search',
            'srsearch' => $query,
            'format' => 'json',
            'srlimit' => 1,
        ]);

        if ($response->failed()) {
            return null;
        }

        $results = $response->json('query.search', []);
        return $results[0]['title'] ?? null;
    }
}
