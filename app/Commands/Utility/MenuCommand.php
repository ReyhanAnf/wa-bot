<?php

namespace App\Commands\Utility;

use App\Contracts\CommandInterface;

class MenuCommand implements CommandInterface
{
    public function handle(array $args, string $waNumber): string|array
    {
        return ['message' => "🤖 *Menu Bot*\n\n" .
               "📌 `/jadwal [hari] [nama]` - Cek jadwal\n" .
               "📝 `/tugas list` - Lihat daftar tugas\n" .
               "➕ `/tugas tambah` - Tambah tugas manual\n" .
               "🤖 `/ai [pertanyaan]` - Tanya AI\n" .
               "🌤️ `/cuaca [kota]` - Info cuaca\n" .
               "🌍 `/gempa` - Info gempa BMKG\n" .
               "🔮 `/kerangajaib` - Tanya kerang ajaib\n" .
               "❤️ `/gombal` - Rayuan maut\n" .
               "🕌 `/jadwalsholat [kota]` - Jadwal sholat\n" .
               "🔗 `/shortlink [url]` - Pendekkan link\n" .
               "📖 `/kbbi [kata]` - Definisi kata\n" .
               "ℹ️ `/menu` - Tampilkan menu ini", 'source' => 'bot_rule'];
    }
}
