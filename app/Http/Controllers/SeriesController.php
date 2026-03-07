<?php

namespace App\Http\Controllers;

use App\Services\TmdbService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SeriesController extends Controller
{
    public function __construct(private TmdbService $tmdb) {}

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:255',
        ]);

        $results = $this->tmdb->search($request->input('q'));

        return response()->json(['results' => $results]);
    }

    public function show(Request $request, int $tmdbId): JsonResponse
    {
        $type = $request->input('type', 'tv');
        $details = $this->tmdb->getDetails($tmdbId, $type);

        if (empty($details)) {
            return response()->json(['message' => 'Series not found'], 404);
        }

        return response()->json($details);
    }

    public function season(int $tmdbId, int $season): JsonResponse
    {
        $details = $this->tmdb->getSeasonDetails($tmdbId, $season);

        if (empty($details)) {
            return response()->json(['message' => 'Season not found'], 404);
        }

        return response()->json($details);
    }
}
