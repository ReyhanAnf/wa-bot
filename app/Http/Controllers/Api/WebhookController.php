<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\BotResponse;
use App\Models\SystemSetting;
use App\Models\Task;
use App\Services\WahaService;
use App\Services\AiService;
use App\Services\CommandRegistry;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WebhookController extends Controller
{
    protected $wahaService;
    protected $aiService;
    protected $commandRegistry;

    public function __construct(
        WahaService $wahaService,
        AiService $aiService,
        CommandRegistry $commandRegistry
    ) {
        $this->wahaService = $wahaService;
        $this->aiService = $aiService;
        $this->commandRegistry = $commandRegistry;
    }

    public function handle(Request $request)
    {
        // 1. Validate & Extract Data
        $input = $request->all();
        Log::info('Webhook Incoming:', $input);

        $payload = $input['payload'] ?? [];
        if (empty($payload)) {
            return response()->json(['status' => 'ignored', 'reason' => 'no_payload']);
        }

        $messageBody = trim($payload['body'] ?? '');

        // Ignore messages from me to prevent loops, UNLESS it's a command
        if (isset($payload['fromMe']) && $payload['fromMe']) {
            if (!str_starts_with($messageBody, '/')) {
                return response()->json(['status' => 'ignored', 'reason' => 'from_me_no_command']);
            }
        }

        // Determine Sender
        // Check for specific JID in _data (LID workaround)
        $remoteJidAlt = $payload['_data']['key']['remoteJidAlt'] ?? null;

        // structure could be 'from', 'author' (group), 'participant', 'chatId'
        $rawId = $remoteJidAlt ?? $payload['from'] ?? $payload['author'] ?? $payload['participant'] ?? $payload['chatId'] ?? null;

        // If rawId is LID and no Alt, maybe we should try to use chatId?
        // But usually chatId is the chat room. If it's a DM to self, chatId is MyNumber.
        // If from is LID, chatId might be MyNumber@c.us?
        // Let's check logs: "from":"67...@lid", "key": { "remoteJid": "67...@lid" ... }
        // BUT Wait, chatId isn't explicitly in the payload top level in the log I saw?
        // "payload": { "id":..., "from":..., "body":... } -> No "chatId" key in top level of that specific log entry.
        // But "from" is the LID.
        // So checking remoteJidAlt is the best bet.

        if (!$rawId || !$messageBody) {
             return response()->json(['status' => 'ignored', 'reason' => 'invalid_data']);
        }

        // Normalize WA Number (remove @c.us, @s.whatsapp.net, @lid)
        $waNumber = preg_replace('/@.+/', '', $rawId);

        // 2. Save User Message
        Chat::create([
            'wa_number' => $waNumber, // Store normalized number for relation
            'message' => $messageBody,
            'source' => 'user',
        ]);

        // 3. Check Task Recording State
        if ($this->handleTaskState($waNumber, $messageBody, $rawId)) {
            return response()->json(['status' => 'success', 'handler' => 'task_state']);
        }

        // 4. Command Pattern Handling (Slash Commands)
        if (str_starts_with($messageBody, '/')) {
            $parts = explode(' ', $messageBody);
            $keyword = strtolower($parts[0]);
            $args = array_slice($parts, 1);

            $command = $this->commandRegistry->getCommand($keyword);

            if ($command) {
                $result = $command->handle($args, $waNumber);

                // Handle response
                if (is_array($result)) {
                    $this->sendResponse($rawId, $waNumber, $result['message'], $result['source'] ?? 'bot_rule');
                } else {
                    $this->sendResponse($rawId, $waNumber, $result, 'bot_rule');
                }

                return response()->json(['status' => 'success', 'handler' => 'command']);
            }
        }

        // 5. Keyword Matching (BotResponse)
        $botResponse = BotResponse::where('is_active', true)
            ->get()
            ->filter(function ($response) use ($messageBody) {
                if ($response->match_type === 'exact') {
                    return strtolower($response->keyword) === strtolower($messageBody);
                }
                return stripos($messageBody, $response->keyword) !== false;
            })
            ->first();

        if ($botResponse) {
            $this->sendResponse($rawId, $waNumber, $botResponse->response, 'bot_rule');
            return response()->json(['status' => 'success', 'handler' => 'keyword']);
        }

        // 6. AI Fallback
        $systemPrompt = SystemSetting::where('key', 'bot_persona')->value('value') ?? 'You are a helpful assistant.';
        $history = Chat::where('wa_number', $waNumber)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->reverse()
            ->map(fn($c) => ['role' => $c->source === 'user' ? 'user' : 'model', 'content' => $c->message])
            ->toArray();

        // Context Awareness: Resolve Contact
        $contact = \App\Models\Contact::where('wa_number', $waNumber)->first();

        // Inject Contact into AI Service
        $aiResponse = $this->aiService->getAnswer($systemPrompt, $history, $messageBody, $contact);
        $this->sendResponse($rawId, $waNumber, $aiResponse, 'bot_ai');

        return response()->json(['status' => 'success', 'handler' => 'ai']);
    }

    protected function handleTaskState(string $waNumber, string $message, string $chatId): bool
    {
        $stateKey = "task_recording_{$waNumber}";
        if (!Cache::has($stateKey)) {
            return false;
        }

        // Handle Special Commands inside state
        if (strtolower($message) === '/batal') {
            Cache::forget($stateKey);
            $this->sendResponse($chatId, $waNumber, "❌ Pencatatan tugas dibatalkan.", 'bot_rule');
            return true; // Handled
        }

        if (strtolower($message) === '/simpan') {
            $lines = Cache::get($stateKey, []);
            if (empty($lines)) {
                Cache::forget($stateKey);
                $this->sendResponse($chatId, $waNumber, "⚠️ Tidak ada data yang disimpan.", 'bot_rule');
                return true;
            }

            $content = implode("\n", $lines);
            $parts = explode("\n", $content, 2);
            $title = substr($parts[0], 0, 255);
            $description = $parts[1] ?? null;

            Task::create([
                'wa_number' => $waNumber,
                'title' => $title,
                'description' => $description,
                'status' => 'pending',
                'priority' => 'medium',
            ]);

            Cache::forget($stateKey);
            $this->sendResponse($chatId, $waNumber, "✅ *Tugas Tersimpan!*", 'bot_rule');
            return true;
        }

        // Append line (Text content)
        // If it's a command like /menu inside recording, we might want to prioritize it?
        // But usually recording captures everything except escape commands.
        // Let's assume everything else is content.

        $lines = Cache::get($stateKey, []);
        $lines[] = $message;
        Cache::put($stateKey, $lines, 600);

        return true; // Handled as state input
    }

    protected function sendResponse($chatId, $waNumber, $message, $source)
    {
        // Save Bot Chat (Use Normalized Number)
        Chat::create([
            'wa_number' => $waNumber,
            'message' => $message,
            'source' => $source,
        ]);

        // Send via Waha (Use Raw ChatId)
        $this->wahaService->sendMessage($chatId, $message);
    }
}
