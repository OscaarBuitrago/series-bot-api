<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\ClaudeService;
use App\Services\PineconeService;
use App\Services\WikipediaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    public function __construct(
        private ClaudeService $claude,
        private PineconeService $pinecone,
        private WikipediaService $wikipedia,
    ) {}

    public function ask(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'question' => 'required|string|max:2000',
            'series_name' => 'required|string|max:255',
            'series_tmdb_id' => 'nullable|integer',
            'season' => 'required|integer|min:1',
            'episode' => 'required|integer|min:1',
            'conversation_id' => 'nullable|integer|exists:conversations,id',
        ]);

        // Get or create conversation
        $conversation = $this->resolveConversation($user, $validated);

        // Build RAG context
        $ragContext = $this->buildRagContext(
            $validated['series_name'],
            $validated['series_tmdb_id'] ?? null,
            $validated['season'],
            $validated['episode'],
            $validated['question']
        );

        // Get conversation history
        $history = $conversation->messages()
            ->latest()
            ->take(10)
            ->get()
            ->reverse()
            ->map(fn($m) => ['role' => $m->role, 'content' => $m->content])
            ->toArray();

        // Call Claude
        $answer = $this->claude->chat(
            seriesName: $validated['series_name'],
            season: $validated['season'],
            episode: $validated['episode'],
            ragContext: $ragContext,
            conversationHistory: $history,
            userQuestion: $validated['question']
        );

        // Persist messages
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $validated['question'],
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $answer,
        ]);

        return response()->json([
            'answer' => $answer,
            'conversation_id' => $conversation->id,
        ]);
    }

    public function conversations(Request $request): JsonResponse
    {
        $conversations = $request->user()
            ->conversations()
            ->with(['messages' => fn($q) => $q->latest()->limit(1)])
            ->latest()
            ->paginate(20);

        return response()->json($conversations);
    }

    public function conversation(Request $request, int $id): JsonResponse
    {
        $conversation = $request->user()
            ->conversations()
            ->with('messages')
            ->findOrFail($id);

        return response()->json($conversation);
    }

    private function resolveConversation($user, array $validated): Conversation
    {
        if (!empty($validated['conversation_id'])) {
            return Conversation::where('user_id', $user->id)
                ->findOrFail($validated['conversation_id']);
        }

        return Conversation::create([
            'user_id' => $user->id,
            'series_tmdb_id' => $validated['series_tmdb_id'] ?? null,
            'series_name' => $validated['series_name'],
            'current_season' => $validated['season'],
            'current_episode' => $validated['episode'],
        ]);
    }

    private function buildRagContext(
        string $seriesName,
        ?int $tmdbId,
        int $season,
        int $episode,
        string $question
    ): string {
        // Try Pinecone first (Phase 2 RAG)
        if ($tmdbId && $this->pinecone->isConfigured()) {
            $filter = $this->pinecone->buildSeriesFilter($tmdbId, $season, $episode);
            $chunks = $this->pinecone->query($question, $filter);
            if (!empty($chunks)) {
                return implode("\n\n", $chunks);
            }
        }

        // Fallback to Wikipedia (Phase 1 MVP)
        return $this->wikipedia->getSummary($seriesName, $season);
    }
}
