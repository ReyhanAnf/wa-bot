<?php

namespace App\Commands\Task;

use App\Contracts\CommandInterface;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class TugasCommand implements CommandInterface
{
    public function handle(array $args, string $waNumber): string|array
    {
        $subCommand = strtolower($args[0] ?? 'list');
        $params = array_slice($args, 1);

        return match ($subCommand) {
            'list' => $this->listTasks($waNumber),
            'tambah' => $this->addTask($waNumber, implode(' ', $params)),
            'detail' => $this->detailTask($waNumber, $params[0] ?? null),
            'selesai' => $this->completeTask($waNumber, $params[0] ?? null),
            'hapus' => $this->deleteTask($waNumber, $params[0] ?? null),
            'simpan' => $this->startRecording($waNumber), // Legacy support for state-based flow
            default => $this->help(),
        };
    }

    protected function listTasks(string $waNumber): array
    {
        $tasks = Task::where('wa_number', $waNumber)
            ->where('status', 'pending')
            ->orderBy('deadline', 'asc')
            ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
            ->get();

        if ($tasks->isEmpty()) {
            return ['message' => "🎉 *Tidak ada tugas pending!* Santai dulu gih.", 'source' => 'bot_rule'];
        }

        $response = "📝 **DAFTAR TUGAS**\n\n";
        foreach ($tasks as $index => $task) {
            $icon = match ($task->priority) {
                'high' => '🔴',
                'medium' => '🟡',
                'low' => '🟢',
                default => '⚪',
            };

            $deadline = $task->deadline ? Carbon::parse($task->deadline)->translatedFormat('d M Y H:i') : '-';
            $response .= "{$index}. [{$icon} " . ucfirst($task->priority) . "] *{$task->title}* (ID: {$task->id})\n";
            $response .= "   ⏳ Deadline: {$deadline}\n";
        }

        $response .= "\nKetik `/tugas detail [ID]` untuk lihat deskripsi.";

        return ['message' => $response, 'source' => 'bot_rule'];
    }

    protected function addTask(string $waNumber, string $input): array
    {
        // Format: Judul | Deadline (Y-m-d) | Priority
        $parts = array_map('trim', explode('|', $input));

        if (count($parts) < 1) {
             return ['message' => "Format salah. Gunakan: `/tugas tambah Judul | YYYY-MM-DD | priority`", 'source' => 'bot_rule'];
        }

        $title = $parts[0];
        $deadline = isset($parts[1]) ? Carbon::parse($parts[1]) : null;
        $priority = strtolower($parts[2] ?? 'medium');

        if (!in_array($priority, ['high', 'medium', 'low'])) {
            $priority = 'medium';
        }

        $task = Task::create([
            'wa_number' => $waNumber,
            'title' => $title,
            'deadline' => $deadline,
            'priority' => $priority,
            'status' => 'pending',
        ]);

        return ['message' => "✅ Tugas **{$task->title}** berhasil ditambahkan!", 'source' => 'bot_rule'];
    }

    protected function detailTask(string $waNumber, ?string $id): array
    {
        if (!$id) return ['message' => "⚠️ Masukkan ID tugas.", 'source' => 'bot_rule'];

        $task = Task::where('wa_number', $waNumber)->find($id);

        if (!$task) return ['message' => "❌ Tugas tidak ditemukan.", 'source' => 'bot_rule'];

        $icon = match ($task->priority) {
            'high' => '🔴',
            'medium' => '🟡',
            'low' => '🟢',
            default => '⚪',
        };

        $deadline = $task->deadline ? Carbon::parse($task->deadline)->translatedFormat('l, d F Y H:i') : '-';

        $response = "📝 **DETAIL TUGAS**\n\n" .
                    "📌 *Judul:* {$task->title}\n" .
                    "🆔 *ID:* {$task->id}\n" .
                    "{$icon} *Prioritas:* " . ucfirst($task->priority) . "\n" .
                    "⏳ *Deadline:* {$deadline}\n" .
                    "📊 *Status:* " . ucfirst($task->status) . "\n\n" .
                    "📝 *Deskripsi:*\n" . ($task->description ?? '-');

        return ['message' => $response, 'source' => 'bot_rule'];
    }

    protected function completeTask(string $waNumber, ?string $id): array
    {
        if (!$id) return ['message' => "⚠️ Masukkan ID tugas.", 'source' => 'bot_rule'];

        $task = Task::where('wa_number', $waNumber)->find($id);

        if (!$task) return ['message' => "❌ Tugas tidak ditemukan.", 'source' => 'bot_rule'];

        $task->update(['status' => 'completed']);

        return ['message' => "✅ Mantap! Tugas **{$task->title}** telah selesai. Tetap produktif ya! 💪", 'source' => 'bot_rule'];
    }

    protected function deleteTask(string $waNumber, ?string $id): array
    {
        if (!$id) return ['message' => "⚠️ Masukkan ID tugas.", 'source' => 'bot_rule'];

        $task = Task::where('wa_number', $waNumber)->find($id);

        if (!$task) return ['message' => "❌ Tugas tidak ditemukan.", 'source' => 'bot_rule'];

        $task->delete();

        return ['message' => "🗑️ Tugas **{$task->title}** berhasil dihapus.", 'source' => 'bot_rule'];
    }

    protected function startRecording(string $waNumber): array
    {
        Cache::put("task_recording_{$waNumber}", [], 600); // 10 mins TTL
        return ['message' => "📝 *Mode Catat Tugas*\n\nSilakan ketik detail tugas Anda\nKetik */simpan* untuk menyimpan atau */batal* untuk membatalkan.", 'source' => 'bot_rule'];
    }

    protected function help(): array
    {
        return ['message' => "🛠️ *Bantuan Tugas*\n\n" .
               "📌 `/tugas list` - Lihat daftar tugas\n" .
               "➕ `/tugas tambah [Judul] | [YYYY-MM-DD] | [priority]` - Tambah tugas\n" .
               "🔍 `/tugas detail [ID]` - Lihat detail\n" .
               "✅ `/tugas selesai [ID]` - Tandai selesai\n" .
               "🗑️ `/tugas hapus [ID]` - Hapus tugas\n" .
               "📝 `/tugas simpan` - Mode catat cepat", 'source' => 'bot_rule'];
    }
}
