<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Chat;
use App\Models\Schedule;
use App\Models\Task;
use App\Services\WahaService;
use App\Services\AiService;
use Mockery;
use Illuminate\Support\Facades\Cache;

class CommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock WahaService globally
        $this->mock(WahaService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')->andReturn(['status' => 'success']);
        });
    }

    public function test_jadwal_command_returns_schedule()
    {
        // Arrange
        Schedule::create([
            'day' => 'Senin',
            'subject' => 'Algoritma',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'lecturer' => 'Pak Budi',
            'room' => 'R.101',
            'is_active' => true,
        ]);

        $payload = [
            'payload' => [
                'from' => '1234567890@c.us',
                'body' => '/jadwal Senin',
            ]
        ];

        // Act
        $response = $this->postJson('/api/webhook/handle', $payload);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('chats', [
            'wa_number' => '1234567890@c.us',
            'source' => 'bot_rule',
        ]);
        // We can't easily assert the content of the bot reply in database without fetching it,
        // but we know WahaService was called (mocked).
        // Let's check the chat record for content.
        $botChat = Chat::where('source', 'bot_rule')->latest()->first();
        $this->assertStringContainsString('Algoritma', $botChat->message);
    }
// ...
    public function test_tugas_flow()
    {
        $waNumber = '1234567890@c.us';

        // 1. Start Recording
        $this->postJson('/api/webhook/handle', [
            'payload' => ['from' => $waNumber, 'body' => '/tugas simpan']
        ]);
        $this->assertTrue(Cache::has("task_recording_{$waNumber}"));

        // 2. Send Task Detail
        $this->postJson('/api/webhook/handle', [
            'payload' => ['from' => $waNumber, 'body' => 'Kerjakan Laporan']
        ]);

        // 3. Send Another Line
        $this->postJson('/api/webhook/handle', [
            'payload' => ['from' => $waNumber, 'body' => 'Deadline besok']
        ]);

        // 4. Save
        $this->postJson('/api/webhook/handle', [
            'payload' => ['from' => $waNumber, 'body' => '/simpan']
        ]);

        // Assert
        $this->assertFalse(Cache::has("task_recording_{$waNumber}"));
        $this->assertDatabaseHas('tasks', [
            'wa_number' => $waNumber,
            'title' => 'Kerjakan Laporan',
            'description' => 'Deadline besok',
            'status' => 'pending',
        ]);
    }

    public function test_ai_command()
    {
        // Mock AiService
        $this->mock(AiService::class, function ($mock) {
            $mock->shouldReceive('getAnswer')->andReturn('This is AI response');
        });

        $payload = [
            'payload' => [
                'from' => '1234567890@c.us',
                'body' => '/ai Hello AI',
            ]
        ];

        $this->postJson('/api/webhook/handle', $payload);

        $this->assertDatabaseHas('chats', [
            'message' => 'This is AI response',
            'source' => 'bot_ai',
        ]);
    }

    public function test_jadwal_with_student_name_filter()
    {
        // Arrange
        Schedule::create([
            'day' => 'Senin',
            'subject' => 'Algoritma',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'lecturer' => 'Pak Budi',
            'student_name' => 'Reyhan',
            'is_active' => true,
        ]);

        Schedule::create([
            'day' => 'Senin',
            'subject' => 'Basis Data',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'lecturer' => 'Bu Ani',
            'student_name' => 'OtherStudent',
            'is_active' => true,
        ]);

        // Act: Filter by 'Reyhan'
        $this->postJson('/api/webhook/handle', [
            'payload' => ['from' => '1234567890@c.us', 'body' => '/jadwal Senin Reyhan']
        ]);

        // Assert
        $botChat = Chat::where('source', 'bot_rule')->latest()->first();
        $this->assertStringContainsString('Algoritma', $botChat->message);
        $this->assertStringNotContainsString('Basis Data', $botChat->message);
    }

    public function test_menu_command_returns_help()
    {
        $this->postJson('/api/webhook/handle', [
            'payload' => ['from' => '1234567890@c.us', 'body' => '/menu']
        ]);

        $this->assertDatabaseHas('chats', [
            'source' => 'bot_rule',
        ]);
        $botChat = Chat::where('source', 'bot_rule')->latest()->first();
        $this->assertStringContainsString('Menu Bot', $botChat->message);
    }
}
