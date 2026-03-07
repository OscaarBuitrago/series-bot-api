<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class ClaudeService
{
    private string $apiKey;
    private string $model = 'gpt-4o-mini';
    private string $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.openai.key');
    }

    public function chat(
        string $seriesName,
        int $season,
        int $episode,
        string $ragContext,
        array $conversationHistory,
        string $userQuestion
    ): string {
        $systemPrompt = $this->buildSystemPrompt($seriesName, $season, $episode, $ragContext);

        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($conversationHistory as $msg) {
            $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $userQuestion];

        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post("{$this->baseUrl}/chat/completions", [
                'model' => $this->model,
                'max_tokens' => 1024,
                'messages' => $messages,
            ]);

        if ($response->failed()) {
            throw new RequestException($response);
        }

        return $response->json('choices.0.message.content', '');
    }

    private function buildSystemPrompt(
        string $seriesName,
        int $season,
        int $episode,
        string $ragContext
    ): string {
        return <<<PROMPT
Eres un asistente experto en series y películas. El usuario está viendo "{$seriesName}",
y ha llegado hasta la temporada {$season}, episodio {$episode}.

REGLA CRÍTICA: No reveles NINGÚN spoiler más allá del episodio {$episode} de la temporada {$season}.
Si el usuario pregunta sobre eventos futuros, indícale amablemente que eso está más adelante de donde va.

Contexto verificado de la serie (usa esto para responder con precisión):
{$ragContext}

Responde en el idioma del usuario. Sé conciso pero completo.
PROMPT;
    }
}
