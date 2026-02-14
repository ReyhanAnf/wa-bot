<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaService
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.waha.base_url', env('WAHA_BASE_URL', 'http://localhost:3000'));
        $this->apiKey = config('services.waha.api_key', env('WAHA_API_KEY'));
    }

    public function sendMessage($to, $message)
    {
        $session = config('services.waha.session') ?: 'SessionTes';
        $url = "{$this->baseUrl}/api/sendText";

        try {
            Log::info("WAHA Sending to {$url}", [
                'chatId' => $to,
                'text' => $message,
                'session' => $session,
            ]);

            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'chatId' => $to,
                'text' => $message,
                'session' => $session,
            ]);

            if ($response->failed()) {
                Log::error('WAHA Send Message Failed: ' . $response->body());
                return false;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('WAHA Service Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Format number to ChatId (e.g., 628123456789@c.us)
     */
    protected function formatChatId($number)
    {
        // Simple formatter, assumes number comes in clean or needs @c.us suffix
        if (!str_contains($number, '@c.us')) {
            return $number . '@c.us';
        }
        return $number;
    }
}
