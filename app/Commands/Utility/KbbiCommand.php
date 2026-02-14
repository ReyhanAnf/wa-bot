<?php

namespace App\Commands\Utility;

use App\Contracts\CommandInterface;
use Illuminate\Support\Facades\Http;

class KbbiCommand implements CommandInterface
{
    public function handle(array $args, string $waNumber): string|array
    {
        $word = $args[0] ?? null;

        if (!$word) {
            return ['message' => "Gunakan: `/kbbi [kata]`", 'source' => 'bot_rule'];
        }

        try {
            // Using a public proxy/unofficial API for KBBI
            $response = Http::get("https://kbbi-api-zhirrr.vercel.app/api/kbbi/text/{$word}");

            if ($response->failed()) {
                return ['message' => "❌ Kata tidak ditemukan atau API bermasalah.", 'source' => 'bot_rule'];
            }

            $data = $response->json();
            // Check if API returns error structure
            if (isset($data['status']) && $data['status'] == false) {
                 return ['message' => "❌ Kata '{$word}' tidak ditemukan di KBBI.", 'source' => 'bot_rule'];
            }

            // Adjust based on typical KBBI API response structure
            // Assuming simplified response or handling generic text
            $text = $data['data']['arti'] ?? $data['arti'] ?? json_encode($data);

            return ['message' => "📖 *KBBI: {$word}*\n\n{$text}", 'source' => 'bot_rule'];

        } catch (\Exception $e) {
            return ['message' => "⚠️ Gagal mengakses KBBI.", 'source' => 'bot_rule'];
        }
    }
}
