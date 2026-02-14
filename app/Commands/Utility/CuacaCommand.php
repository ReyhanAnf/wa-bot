<?php

namespace App\Commands\Utility;

use App\Contracts\CommandInterface;
use Illuminate\Support\Facades\Http;

class CuacaCommand implements CommandInterface
{
    public function handle(array $args, string $waNumber): string|array
    {
        $city = implode(' ', $args);
        if (empty($city)) {
            return ['message' => "Gunakan: `/cuaca [nama_kota]` (contoh: `/cuaca Jakarta`)", 'source' => 'bot_rule'];
        }

        try {
            // Geocoding first to get lat/lon
            $geoResponse = Http::get("https://geocoding-api.open-meteo.com/v1/search", [
                'name' => $city,
                'count' => 1,
                'language' => 'id',
                'format' => 'json',
            ]);

            if ($geoResponse->failed() || empty($geoResponse->json()['results'])) {
                return ['message' => "❌ Kota tidak ditemukan.", 'source' => 'bot_rule'];
            }

            $result = $geoResponse->json()['results'][0];
            $lat = $result['latitude'];
            $lon = $result['longitude'];
            $name = $result['name'];

            // Get Weather
            $weatherResponse = Http::get("https://api.open-meteo.com/v1/forecast", [
                'latitude' => $lat,
                'longitude' => $lon,
                'current_weather' => true,
                'timezone' => 'auto',
            ]);

            if ($weatherResponse->failed()) {
                return ['message' => "❌ Gagal mengambil data cuaca.", 'source' => 'bot_rule'];
            }

            $weather = $weatherResponse->json()['current_weather'];
            $temp = $weather['temperature'];
            $wind = $weather['windspeed'];

            // WMO Weather interpretation code (simplified)
            $code = $weather['weathercode'];
            $condition = match (true) {
                $code <= 3 => 'Cerah Berawan 🌤️',
                $code <= 48 => 'Berkabut 🌫️',
                $code <= 67 => 'Hujan 🌧️',
                $code <= 77 => 'Salju ❄️',
                $code >= 80 => 'Hujan Badai ⛈️',
                default => 'Berawan ☁️',
            };

            return ['message' => "🌤️ *Cuaca di {$name}*\n\n" .
                   "🌡️ Suhu: {$temp}°C\n" .
                   "🌬️ Angin: {$wind} km/h\n" .
                   "usahakan bawa payung ya! {$condition}", 'source' => 'bot_rule'];

        } catch (\Exception $e) {
            return ['message' => "⚠️ Terjadi kesalahan koneksi.", 'source' => 'bot_rule'];
        }
    }
}
