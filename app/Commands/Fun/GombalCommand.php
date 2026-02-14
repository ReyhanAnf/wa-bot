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
            "Kamu tau gak bedanya kamu sama Borobudur? Borobudur itu candi, kalau kamu itu candu. 🏯😍",
            "Kamu itu kayak garam di lautan, gak kelihatan tapi selalu ada rasanya. 🌊🧂",
            "Kamu tau gak kenapa menara pisa miring? Karena ketarik sama senyumanmu. 🗼😊",
            "Kalau kamu jadi bunga, aku rela jadi kumbangnya. 🌻🐝",
            "Aku rela ditangkap polisi, asalkan tuduhannya mencuri hatimu. 👮‍♂️💘",
            "Kamu itu kayak wifi, bikin aku pengen konek terus. 📶💖",
            "Kalau disuruh milih antara nafas sama kamu, aku milih nafas terakhir buat bilang aku sayang kamu. 🌬️💕",
            "Kamu itu kayak pelangi, indah tapi susah digapai. Eh salah, indah dan bikin hari-hariku berwarna. 🌈✨",
            "Tau gak persamaan kamu sama soal ujian? Sama-sama perlu diperjuangin. 📝💪",
            "Kamu itu kayak kopi, pait sih tapi bikin nagih. ☕😋",
            "Jangan GR deh, aku kangen kamu sedikit aja kok. Sedikit berlebihan maksudnya. 🤪",
            "Kamu tau gak bedanya cincin sama kamu? Cincin melekat di jari, kalau kamu melekat di hati. 💍❤️",
            "Aku gak sedih kok nungguin kamu, soalnya aku tau yang indah itu butuh waktu. ⏳🌹",
            "Kamu itu kayak bintang, jauh tapi selalu bersinar di hatiku. ⭐💖",
            "Kalau aku jadi superhero, aku gak mau jadi Superman atau Batman. Aku mau jadi Yourman. 🦸‍♂️😉",
            "Kamu tau gak kenappa aku suka ngemil? Ngemilikin kamu seutuhnya. 🍟🥰",
            "Cintaku padamu itu kayak utang, awalnya kecil lama-lama gede sendiri. 💸💘",
            "Kamu itu kayak AC, bikin adem terus. ❄️😌",
            "Tau gak bedanya kamu sama kipas angin? Kipas angin bikin masuk angin, kalau kamu bikin kangen. 🌬️🤗",
            "Kamu itu kayak lampu merah, bikin aku berhenti buat mandangin kamu. 🚦😍",
        ];

        $random = $gombalan[array_rand($gombalan)];

        return ['message' => $random, 'source' => 'bot_rule'];
    }
}
