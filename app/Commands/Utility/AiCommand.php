<?php

namespace App\Commands\Utility;

use App\Contracts\CommandInterface;
use App\Services\AiService;
use App\Models\Chat;
use App\Models\SystemSetting;

class AiCommand implements CommandInterface
{
    protected $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function handle(array $args, string $waNumber): string|array
    {
        $prompt = implode(' ', $args);

        if (empty($prompt)) {
            return ['message' => "Gunakan: `/ai pertanyaan kamu`", 'source' => 'bot_rule'];
        }

        // Get System Prompt
        $systemPrompt = SystemSetting::where('key', 'bot_persona')->value('value') ?? 'You are a helpful assistant.';

        // Get Context (last 5 messages)
        $history = Chat::where('wa_number', $waNumber)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->reverse()
            ->map(function ($chat) {
                return [
                    'role' => $chat->source === 'user' ? 'user' : 'model',
                    'content' => $chat->message
                ];
            })
            ->toArray();

        $response = $this->aiService->getAnswer($systemPrompt, $history, $prompt);
        return ['message' => $response, 'source' => 'bot_ai'];
    }
}
