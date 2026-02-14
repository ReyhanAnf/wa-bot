<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Chat;
use App\Services\WahaService;
use Mockery;
use Illuminate\Support\Facades\Http;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_handles_incoming_message()
    {
        // Mock WahaService
        $this->mock(WahaService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')->andReturn(['status' => 'success']);
        });

        $payload = [
            'event' => 'message',
            'payload' => [
                'id' => 'false_11111111111@c.us_AAAAAAAAAAAAAAAAAAAA',
                'timestamp' => 1666943582,
                'from' => '1234567890@c.us',
                'fromMe' => false,
                'body' => 'Hello Bot',
            ]
        ];

        $response = $this->postJson('/api/webhook/handle', $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('chats', [
            'wa_number' => '1234567890@c.us',
            'message' => 'Hello Bot',
            'source' => 'user',
        ]);
    }

    public function test_webhook_ignores_message_from_me()
    {
        $payload = [
            'event' => 'message',
            'payload' => [
                'from' => '1234567890@c.us',
                'fromMe' => true, // Sent by me
                'body' => 'Hello myself',
            ]
        ];

        $response = $this->postJson('/api/webhook/handle', $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ignored', 'reason' => 'from_me']);

        $this->assertDatabaseMissing('chats', [
            'message' => 'Hello myself',
        ]);
    }

    public function test_webhook_handles_invalid_payload()
    {
        $payload = [
            'event' => 'message',
            // Missing payload
        ];

        $response = $this->postJson('/api/webhook/handle', $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ignored', 'reason' => 'no_payload']);
    }
}
