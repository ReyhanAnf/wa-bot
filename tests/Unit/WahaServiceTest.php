<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\WahaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class WahaServiceTest extends TestCase
{
    public function test_send_message_constructs_correct_request()
    {
        // Mock config
        Config::set('services.waha.base_url', 'http://waha.example');
        Config::set('services.waha.api_key', 'test-key');
        Config::set('services.waha.session', 'SessionTes');

        // Mock Http
        Http::fake([
            'http://waha.example/api/sendText' => Http::response(['id' => 'msg_123'], 200),
        ]);

        $service = new WahaService();
        $response = $service->sendMessage('1234567890@c.us', 'Hello World');

        // Assert Request was sent to correct URL and with correct Body
        Http::assertSent(function ($request) {
            return $request->url() === 'http://waha.example/api/sendText' &&
                   $request['chatId'] === '1234567890@c.us' &&
                   $request['text'] === 'Hello World' &&
                   $request['session'] === 'SessionTes'; // verify session IS in body
        });
    }
}
