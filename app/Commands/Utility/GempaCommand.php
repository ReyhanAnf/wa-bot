<?php

namespace App\Commands\Utility;

use App\Contracts\CommandInterface;
use Illuminate\Support\Facades\Http;

class GempaCommand implements CommandInterface
{
    public function handle(array $args, string $waNumber): string|array
    {
        try {
            $response = Http::get("https://data.bmkg.go.id/DataMKG/TEWS/autogempa.json");

            if ($response->failed()) {
                return ['message' => "❌ Gagal mengambil data BMKG.", 'source' => 'bot_rule'];
            }

            $data = $response->json()['Infogempa']['gempa'];

            return ['message' => "🌍 *Info Gempa Terkini (BMKG)*\n\n" .
                   "📅 Waktu: {$data['Tanggal']} {$data['Jam']}\n" .
                   "📊 Magnitudo: {$data['Magnitude']}\n" .
                   "🌊 Kedalaman: {$data['Kedalaman']}\n" .
                   "📍 Lokasi: {$data['Wilayah']}\n" .
                   "⚠️ Potensi: {$data['Potensi']}\n" .
                   "Stay safe ya! 🙏", 'source' => 'bot_rule'];

        } catch (\Exception $e) {
            return ['message' => "⚠️ Terjadi kesalahan koneksi ke BMKG.", 'source' => 'bot_rule'];
        }
    }
}
