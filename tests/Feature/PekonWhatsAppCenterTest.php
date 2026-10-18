<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PekonWhatsAppCenterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('waha_whatsapp_center_info');
    }

    public function test_can_get_whatsapp_center_info_fallback(): void
    {
        // When WAHA is unreachable, fallback should kick in seamlessly
        Http::fake([
            '*/api/sessions*' => Http::response(null, 500),
        ]);

        $response = $this->getJson('/api/public/whatsapp-center');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'phone_number' => '6285185222303',
                    'source' => 'fallback',
                    'is_online' => false,
                ],
            ]);

        $this->assertStringContainsString('6285185222303', $response->json('data.wa_link'));
    }

    public function test_can_get_whatsapp_center_info_from_waha_session_me(): void
    {
        Http::fake([
            '*/api/sessions/*/me' => Http::response([
                'id' => '6285185222303@c.us',
                'pushName' => 'Pekon Margodadi Center',
            ], 200),
        ]);

        $response = $this->getJson('/api/public/whatsapp-center');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'phone_number' => '6285185222303',
                    'push_name' => 'Pekon Margodadi Center',
                    'source' => 'waha',
                    'is_online' => true,
                ],
            ]);

        $this->assertStringContainsString('6285185222303', $response->json('data.wa_link'));
    }

    public function test_direct_alias_route_works(): void
    {
        Http::fake([
            '*/api/sessions*' => Http::response(null, 500),
        ]);

        $response = $this->getJson('/api/whatsapp-center');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);
    }
}
