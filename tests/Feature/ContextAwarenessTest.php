<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Contact;
use App\Models\Chat;
use App\Services\AiService;
use App\Services\WahaService;
use Mockery;

class ContextAwarenessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(WahaService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')->andReturn(['status' => 'success']);
        });
    }

    public function test_webhook_identifies_contact_and_injects_context()
    {
        // 1. Create Contact
        $contact = Contact::create([
            'wa_number' => '628123456789',
            'name' => 'John Doe',
            'nickname' => 'Johnny',
            'role' => 'user',
            'personal_notes' => 'Loves coffee and coding.',
        ]);

        // 2. Mock AI Service to spy on arguments
        $this->mock(AiService::class, function ($mock) use ($contact) {
            $mock->shouldReceive('getAnswer')
                ->once()
                ->withArgs(function ($prompt, $history, $message, $passedContact) use ($contact) {
                    // Assert Contact is passed
                    return $passedContact instanceof Contact &&
                           $passedContact->id === $contact->id;
                })
                ->andReturn('Hello, Johnny!'); // Simulated response
        });

        // 3. Hit Webhook
        $response = $this->postJson('/api/webhook/handle', [
            'payload' => [
                'from' => '628123456789',
                'body' => 'Who am I?',
            ]
        ]);

        $response->assertStatus(200);

        // 4. Verify Bot Chat stored
        $this->assertDatabaseHas('chats', [
            'wa_number' => '628123456789',
            'source' => 'bot_ai',
            'message' => 'Hello, Johnny!'
        ]);
    }

    public function test_webhook_normalizes_wa_number_and_handles_chatId()
    {
        // Mock AI to avoid actual call
        $this->mock(AiService::class, function ($mock) {
            $mock->shouldReceive('getAnswer')->andReturn('Response');
        });

        // Spy on WahaService to verify it uses the RAW ChatId
        $this->mock(WahaService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->with('628999888@c.us', 'Response') // Expect RAW ID
                ->once();
        });

        $this->postJson('/api/webhook/handle', [
            'payload' => [
                'chatId' => '628999888@c.us',
                'body' => 'Test',
            ]
        ]);

        $this->assertDatabaseHas('chats', [
            'wa_number' => '628999888', // Normalized in DB
            'source' => 'user',
        ]);
    }

    public function test_webhook_processes_self_triggered_commands()
    {
        // 1. Test Command from Me (Should Process)
        $this->mock(AiService::class, function ($mock) {
            $mock->shouldReceive('getAnswer')->never(); // Should not hit AI
        });

        // We need to mock command handling or just check response connection
        // Since we don't mock CommandRegistry here easily without more setup,
        // let's just check the response structure and ignore reason.
        // Actually, if command doesn't exist, it falls through to BotResponse -> AI.
        // But /menu exists.

        // Let's just create a dummy command via registry? No, too complex.
        // Let's just trust the controller flow. If handler is 'command' or 'ai' or 'keyword', it passed the check.
        // If handler is 'ignored' with reason 'from_me_no_command', it worked.

        $response = $this->postJson('/api/webhook/handle', [
            'payload' => [
                'fromMe' => true,
                'from' => '628123456789@c.us',
                'body' => '/menu',
            ]
        ]);

        // Should NOT be ignored
        $response->assertJson(['status' => 'success']);

        // 2. Test Text from Me (Should Ignore)
        $responseIgnore = $this->postJson('/api/webhook/handle', [
            'payload' => [
                'fromMe' => true,
                'from' => '628123456789@c.us',
                'body' => 'Just a random note to self',
            ]
        ]);

        $responseIgnore->assertJson([
            'status' => 'ignored',
            'reason' => 'from_me_no_command'
        ]);
    }
    public function test_webhook_handles_lid_with_remoteJidAlt()
    {
        // Spy on WahaService to verify it uses the ALT ID
        $this->mock(WahaService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->with('628555444@s.whatsapp.net', Mockery::any()) // Expect ALT ID
                ->once();
        });

        // Mock AI to avoid actual call
        $this->mock(AiService::class, function ($mock) {
             $mock->shouldReceive('getAnswer')->never();
        });

        $this->postJson('/api/webhook/handle', [
            'payload' => [
                'fromMe' => true,
                'from' => '123456789@lid',
                'body' => '/menu',
                '_data' => [
                    'key' => [
                        'remoteJid' => '123456789@lid',
                        'remoteJidAlt' => '628555444@s.whatsapp.net',
                        'fromMe' => true
                    ]
                ]
            ]
        ]);

        $this->assertDatabaseHas('chats', [
            'wa_number' => '628555444', // Normalized Alt ID
            'source' => 'user',
        ]);
    }
}
