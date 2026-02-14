<?php

namespace App\Commands\Utility;

use App\Contracts\CommandInterface;
use Illuminate\Support\Facades\Http;

class ShortlinkCommand implements CommandInterface
{
    public function handle(array $args, string $waNumber): string|array
    {
        $url = $args[0] ?? null;

        if (!$url) {
            return ['message' => "Gunakan: `/shortlink [url panjang]`", 'source' => 'bot_rule'];
        }

        try {
            $response = Http::get("https://is.gd/create.php", [
                'format' => 'simple',
                'url' => $url,
            ]);

            if ($response->failed()) {
                return ['message' => "❌ Gagal memendekkan link.", 'source' => 'bot_rule'];
            }

            $shortUrl = $response->body();

            return ['message' => "🔗 Link Pendek: {$shortUrl}", 'source' => 'bot_rule'];

        } catch (\Exception $e) {
            return ['message' => "⚠️ Terjadi kesalahan saat memendekkan link.", 'source' => 'bot_rule'];
        }
    }
}
