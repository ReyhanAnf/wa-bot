<?php

namespace App\Commands\Fun;

use App\Contracts\CommandInterface;

class GombalCommand implements CommandInterface
{
    public function handle(array $args, string $waNumber): string|array
    {
        $gombalan = [
            "Kamu itu kayak lempengan bumi, geser dikit aja bisa gempain hatiku. 🌍❤️",
            "Bapak kamu tukang las ya? Soalnya kamu telah menyatukan hatiku yang hancur. 🔥",
            "Tau gak bedanya kamu sama jam 12? Kalau jam 12 kesiangan, kalau kamu kesayangan. 🕰️💕",
            "Muka kamu kok kayak orang susah sih? Susah dilupain maksudnya. 😜",
            "Cuka apa yang manis? Cuka sama kamu. 🍬",
            "Kamu punya peta gak? Aku tersesat di matamu. 🗺️👀",
            "Kalo kamu jadi senar gitar, aku nggak mau jadi gitarisnya. Aku nggak mau mutusin kamu. 🎸",
            "Panda panda apa yang bikin seneng? Pandangin kamu setiap hari. 🐼🥰",
        ];

        $random = $gombalan[array_rand($gombalan)];

        return ['message' => $random, 'source' => 'bot_rule'];
    }
}
