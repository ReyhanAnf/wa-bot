<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Task;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CommandService
{
    protected $aiService;
    protected $wahaService;

    public function __construct(AiService $aiService, WahaService $wahaService)
    {
        $this->aiService = $aiService;
        $this->wahaService = $wahaService;
    }

    public function handle($waNumber, $message)
    {
        $message = trim($message);

        // Check for active state (Task Recording)
        $stateKey = "task_recording_{$waNumber}";
        if (Cache::has($stateKey)) {
            return $this->handleTaskRecording($waNumber, $message, $stateKey);
        }

        // Check for slash commands
        if (!str_starts_with($message, '/')) {
            return null; // Not a command
        }

        $parts = explode(' ', $message);
        $command = strtolower($parts[0]);
        $args = array_slice($parts, 1);

        return match ($command) {
            '/jadwal' => $this->handleJadwal($args),
            '/tugas' => $this->handleTugas($waNumber, $args),
            '/ai' => $this->handleAi($waNumber, implode(' ', $args)),
            '/help', '/menu' => $this->handleHelp(),
            default => null,
        };
    }

    protected function handleJadwal($args)
    {
        $day = ucfirst(strtolower($args[0] ?? date('l'))); // Default to today if no arg
        // Map English days to Indonesian if needed, or assume user typse Indonesian
        // Simple mapping for 'today' logic:
        $daysMap = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
        ];
        if (isset($daysMap[$day])) {
            $day = $daysMap[$day];
        }

        $filterName = $args[1] ?? null;

        $query = Schedule::where('day', $day)->where('is_active', true);

        if ($filterName) {
            $query->where(function ($q) use ($filterName) {
                // Priority: student_name match implies personal schedule
                $q->where('student_name', 'like', "%{$filterName}%")
                  ->orWhere('lecturer', 'like', "%{$filterName}%")
                  ->orWhere('subject', 'like', "%{$filterName}%");
            });
        }

        $schedules = $query->orderBy('start_time')->get();

        if ($schedules->isEmpty()) {
            return ['message' => "📅 *Jadwal {$day}*: Tidak ada jadwal" . ($filterName ? " untuk query '{$filterName}'" : "") . ".", 'source' => 'bot_rule'];
        }

        $response = "📅 *Jadwal {$day}* " . ($filterName ? "(Filter: {$filterName})" : "") . ":\n";
        foreach ($schedules as $schedule) {
            $start = substr($schedule->start_time, 0, 5);
            $end = substr($schedule->end_time, 0, 5);
            $response .= "\n🕒 {$start} - {$end}";
            $response .= "\n📚 *{$schedule->subject}*";
            if ($schedule->student_name) $response .= "\n👤 {$schedule->student_name}";
            if ($schedule->lecturer) $response .= "\n👨‍🏫 {$schedule->lecturer}";
            if ($schedule->room) $response .= "\n📍 {$schedule->room}";
            $response .= "\n-------------------";
        }

        return ['message' => $response, 'source' => 'bot_rule'];
    }

    protected function handleTugas($waNumber, $args)
    {
        $action = strtolower($args[0] ?? '');

        if ($action === 'simpan') {
            Cache::put("task_recording_{$waNumber}", [], 600); // 10 mins TTL
            return ['message' => "📝 *Mode Catat Tugas*\n\nSilakan ketik detail tugas Anda\nKetik */simpan* untuk menyimpan atau */batal* untuk membatalkan.", 'source' => 'bot_rule'];
        }

        return ['message' => "Format salah. Gunakan: `/tugas simpan`", 'source' => 'bot_rule'];
    }

    protected function handleTaskRecording($waNumber, $message, $stateKey)
    {
        if (strtolower(trim($message)) === '/simpan') {
            $lines = Cache::get($stateKey, []);
            if (empty($lines)) {
                Cache::forget($stateKey);
                return ['message' => "⚠️ Tidak ada data yang disimpan.", 'source' => 'bot_rule'];
            }

            $content = implode("\n", $lines);
            // First line as title, rest as description
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
            return ['message' => "✅ *Tugas Tersimpan!*", 'source' => 'bot_rule'];
        }

        if (strtolower(trim($message)) === '/batal') {
            Cache::forget($stateKey);
            return ['message' => "❌ Pencatatan tugas dibatalkan.", 'source' => 'bot_rule'];
        }

        // Append line to cache
        $lines = Cache::get($stateKey, []);
        $lines[] = $message;
        Cache::put($stateKey, $lines, 600);

        return null;
    }

    protected function handleAi($waNumber, $prompt)
    {
        if (empty($prompt)) {
            return ['message' => "Gunakan: `/ai pertanyaan kamu`", 'source' => 'bot_rule'];
        }

        // Get System Prompt
        $systemPrompt = \App\Models\SystemSetting::where('key', 'bot_persona')->value('value') ?? 'You are a helpful assistant.';

        // Get Context (last 5 messages)
        $history = \App\Models\Chat::where('wa_number', $waNumber)
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

    protected function handleHelp()
    {
        return ['message' => "🤖 *Menu Bot*\n\n" .
               "📌 */jadwal [hari] [nama]* - Cek jadwal (Opsional: nama mahasiswa)\n" .
               "📝 */tugas simpan* - Catat tugas baru\n" .
               "🤖 */ai [pertanyaan]* - Tanya AI\n" .
               "ℹ️ */menu* - Tampilkan menu ini", 'source' => 'bot_rule'];
    }
}
