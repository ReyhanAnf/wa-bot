<?php

namespace App\Commands\Fun;

use App\Contracts\CommandInterface;

class KalkulatorCintaCommand implements CommandInterface
{
    public function handle(array $args, string $waNumber): string|array
    {
        if (count($args) < 2) {
             return ['message' => "💘 Gunakan: `/kalkulatorcinta [nama1] [nama2]`", 'source' => 'bot_rule'];
        }

        $nama1 = ucfirst($args[0]);
        $nama2 = ucfirst($args[1]);

        // Simple hashing for consistent results for same pairs
        $percentage = abs(crc32($nama1 . $nama2)) % 101;

        $comment = match (true) {
            $percentage > 90 => "Cocok banget! Nikah yuk! 💍",
            $percentage > 75 => "Pasangan serasi! 🥰",
            $percentage > 50 => "Boleh lah dicoba. 🤔",
            $percentage > 25 => "Cukup sulit... 😬",
            default => "Mending cari yang lain. 💀",
        };

        return ['message' => "💘 *Kalkulator Cinta*\n\n" .
               "🤵 {$nama1} ❤️ 👰 {$nama2}\n" .
               "📊 Kecocokan: {$percentage}%\n\n" .
               "📝 {$comment}", 'source' => 'bot_rule'];
    }
}
