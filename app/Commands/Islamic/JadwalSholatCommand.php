<?php

namespace App\Commands\Islamic;

use App\Contracts\CommandInterface;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class JadwalSholatCommand implements CommandInterface
{
    public function handle(array $args, string $waNumber): string|array
    {
        $city = implode(' ', $args);

        if (empty($city)) {
             return ['message' => "🕌 Gunakan: `/jadwalsholat [nama_kota]`", 'source' => 'bot_rule'];
        }

        try {
            // MyQuran API Search City ID
            $searchResponse = Http::get("https://api.myquran.com/v2/sholat/kota/cari/{$city}");

            if ($searchResponse->failed() || empty($searchResponse->json()['data'])) {
                return ['message' => "❌ Kota tidak ditemukan.", 'source' => 'bot_rule'];
            }

            $cityData = $searchResponse->json()['data'][0];
            $cityId = $cityData['id'];
            $cityName = $cityData['lokasi'];

            // Get Schedule
            $date = Carbon::now()->format('Y/m/d');
            $scheduleResponse = Http::get("https://api.myquran.com/v2/sholat/jadwal/{$cityId}/{$date}");

            if ($scheduleResponse->failed()) {
                return ['message' => "❌ Gagal mengambil jadwal sholat.", 'source' => 'bot_rule'];
            }

            $jadwal = $scheduleResponse->json()['data']['jadwal'];

            return ['message' => "🕌 *Jadwal Sholat {$cityName}*\n" .
                   "📅 {$jadwal['tanggal']}\n\n" .
                   "🌌 Imsak: {$jadwal['imsak']}\n" .
                   "🌅 Subuh: {$jadwal['subuh']}\n" .
                   "🌄 Terbit: {$jadwal['terbit']}\n" .
                   "🌞 Dhuha: {$jadwal['dhuha']}\n" .
                   "☀️ Dzuhur: {$jadwal['dzuhur']}\n" .
                   "🌤️ Ashar: {$jadwal['ashar']}\n" .
                   "🌅 Maghrib: {$jadwal['maghrib']}\n" .
                   "🌙 Isya: {$jadwal['isya']}", 'source' => 'bot_rule'];

        } catch (\Exception $e) {
            return ['message' => "⚠️ Terjadi kesalahan koneksi.", 'source' => 'bot_rule'];
        }
    }
}
