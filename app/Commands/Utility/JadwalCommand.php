<?php

namespace App\Commands\Utility;

use App\Contracts\CommandInterface;
use App\Models\Schedule;

class JadwalCommand implements CommandInterface
{
    public function handle(array $args, string $waNumber): string|array
    {
        $day = ucfirst(strtolower($args[0] ?? date('l')));

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
}
