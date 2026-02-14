<?php

namespace App\Commands\Fun;

use App\Contracts\CommandInterface;

class KerangAjaibCommand implements CommandInterface
{
    public function handle(array $args, string $waNumber): string|array
    {
        $question = implode(' ', $args);

        if (empty($question)) {
            return ['message' => "🐚 Gunakan: `/kerangajaib [pertanyaan]`\nContoh: `/kerangajaib Apakah aku gantenk?`", 'source' => 'bot_rule'];
        }

        $answers = [
            "Ya.",
            "Tidak.",
            "Mungkin.",
            "Coba tanya lagi nanti.",
            "Jangan berharap.",
            "Pasti!",
            "Sangat meragukan.",
            "Tanda-tandanya bagus.",
        ];

        $random = $answers[array_rand($answers)];

        return ['message' => "🐚 *Kerang Ajaib Berkata:*\n\n\"{$random}\"", 'source' => 'bot_rule'];
    }
}
