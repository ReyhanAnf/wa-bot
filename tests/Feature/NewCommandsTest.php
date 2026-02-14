<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Models\Chat;
use App\Services\WahaService;

class NewCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(WahaService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')->andReturn(['status' => 'success']);
        });
    }

    public function test_cuaca_command()
    {
        // Mock Geocoding & Weather API
        Http::fake([
            'geocoding-api.open-meteo.com/*' => Http::response([
                'results' => [['name' => 'Jakarta', 'latitude' => -6.2, 'longitude' => 106.8]]
            ], 200),
            'api.open-meteo.com/*' => Http::response([
                'current_weather' => ['temperature' => 30, 'windspeed' => 10, 'weathercode' => 1]
            ], 200),
        ]);

        $this->postJson('/api/webhook/handle', [
            'payload' => ['from' => '123@c.us', 'body' => '/cuaca Jakarta']
        ]);

        $botChat = Chat::where('source', 'bot_rule')->latest()->first();
        $this->assertStringContainsString('Jakarta', $botChat->message);
        $this->assertStringContainsString('30°C', $botChat->message);
    }

    public function test_gempa_command()
    {
        Http::fake([
            'data.bmkg.go.id/*' => Http::response([
                'Infogempa' => [
                    'gempa' => [
                        'Tanggal' => '14 Feb 2026',
                        'Jam' => '10:00:00 WIB',
                        'Magnitude' => '5.0',
                        'Kedalaman' => '10 km',
                        'Wilayah' => 'Laut Banda',
                        'Potensi' => 'Tidak berpotensi tsunami'
                    ]
                ]
            ], 200),
        ]);

        $this->postJson('/api/webhook/handle', [
            'payload' => ['from' => '123@c.us', 'body' => '/gempa']
        ]);

        $botChat = Chat::where('source', 'bot_rule')->latest()->first();
        $this->assertStringContainsString('Info Gempa', $botChat->message);
        $this->assertStringContainsString('5.0', $botChat->message);
    }

    public function test_kerang_ajaib_command()
    {
        $this->postJson('/api/webhook/handle', [
            'payload' => ['from' => '123@c.us', 'body' => '/kerangajaib Apakah aku lulus?']
        ]);
        $botChat = Chat::where('source', 'bot_rule')->latest()->first();
        $this->assertStringContainsString('Kerang Ajaib', $botChat->message);
    }

    public function test_gombal_command()
    {
        $this->postJson('/api/webhook/handle', [
            'payload' => ['from' => '123@c.us', 'body' => '/gombal']
        ]);
        $botChat = Chat::where('source', 'bot_rule')->latest()->first();
        $this->assertNotEmpty($botChat->message);
    }

    public function test_cek_khodam_command()
    {
        $this->postJson('/api/webhook/handle', [
            'payload' => ['from' => '123@c.us', 'body' => '/cekkhodam Reyhan']
        ]);
        $botChat = Chat::where('source', 'bot_rule')->latest()->first();
        $this->assertStringContainsString('Khodam', $botChat->message);
    }
}
