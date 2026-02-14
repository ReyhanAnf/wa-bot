<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    protected $apiKey;
    protected $model;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        // Default ke model Flash terbaru (cepat & hemat)
        // Pastikan di .env: GEMINI_MODEL=gemini-2.0-flash
        $this->model = env('GEMINI_MODEL', 'gemini-2.0-flash');
    }

    /**
     * Get answer from AI
     *
     * @param string $systemPrompt Instruksi utama (Persona bot)
     * @param array $context History chat [['role' => 'user', 'content' => '...'], ...]
     * @param string $newMessage Pesan baru dari user
     * @return string|null
     */
    public function getAnswer(string $systemPrompt, array $context, string $newMessage, ?\App\Models\Contact $contact = null): ?string
    {
        if ($contact) {
            $contextString = "\n\n[USER CONTEXT]\n";
            $contextString .= "Name: " . $contact->name . "\n";
            if ($contact->nickname) $contextString .= "Nickname: " . $contact->nickname . "\n";
            if ($contact->personal_notes) $contextString .= "Facts: " . $contact->personal_notes . "\n";
            $contextString .= "[END USER CONTEXT]\n";
            $systemPrompt .= $contextString;
        }
        // Endpoint API v1beta (Wajib v1beta untuk fitur systemInstruction)
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        // --- 1. KONFIGURASI GAYA BAHASA & FORMAT WA ---
        // Kita gabungkan prompt persona dari database dengan aturan format WA
        $whatsappRules = <<<EOT

        [ATURAN FORMAT WHATSAPP - WAJIB PATUH]:
        1. Jangan gunakan Heading Markdown (# Heading). Ganti dengan BOLD (*Judul*).
        2. Gunakan *teks tebal* untuk penekanan/poin penting.
        3. Gunakan _teks miring_ untuk istilah asing.
        4. Gunakan bullet point (-) yang rapi.
        5. Jika ada kode program, WAJIB apit dengan ``` (triple backticks).
        6. Jawab dengan ringkas, padat, dan "manusiawi" (jangan kaku seperti robot).
        7. Maksimal 1 paragraf terdiri dari 2-3 kalimat agar enak dibaca di HP.
        EOT;

        $finalSystemInstruction = $systemPrompt . "\n" . $whatsappRules;

        // --- 2. SUSUN HISTORY CHAT (CONTEXT) ---
        $contents = [];

        // Masukkan history chat sebelumnya (jika ada)
        foreach ($context as $msg) {
            // Mapping role: 'user' -> 'user', 'bot' -> 'model'
            $role = ($msg['role'] === 'user') ? 'user' : 'model';

            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $msg['content']]]
            ];
        }

        // Masukkan pesan baru user
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $newMessage]]
        ];

        try {
            // --- 3. KIRIM REQUEST ---
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                // Fitur 'systemInstruction' memisahkan instruksi dari pesan user (Lebih Akurat)
                'systemInstruction' => [
                    'parts' => [
                        ['text' => $finalSystemInstruction]
                    ]
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.7,      // 0.7 pas untuk chat luwes tapi tidak halu
                    'maxOutputTokens' => 1000, // Cukup untuk ~500 kata (WA friendly)
                    'topP' => 0.9,             // Menjaga relevansi jawaban
                    'topK' => 40,
                ],
            ]);

            if ($response->failed()) {
                Log::error('AI Service Failed: ' . $response->body());
                return "Maaf, server AI sedang sibuk. Silakan coba sesaat lagi.";
            }

            $data = $response->json();

            // Ambil text jawaban
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }

            Log::warning('AI Empty Response: ' . json_encode($data));
            return null;

        } catch (\Exception $e) {
            Log::error('AI Service Exception: ' . $e->getMessage());
            return null;
        }
    }
}
