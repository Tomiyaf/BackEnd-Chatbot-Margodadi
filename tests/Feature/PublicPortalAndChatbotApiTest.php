<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Operator;
use App\Models\PublicService;
use App\Models\ServiceCategory;
use App\Models\Umkm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPortalAndChatbotApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $cat = ServiceCategory::create([
            'name' => 'Surat Pengantar & Keterangan',
            'slug' => 'surat-pengantar',
            'domain' => \App\Enums\ServiceDomain::PUBLIC_SERVICE,
            'icon' => 'description',
            'is_active' => true,
        ]);

        PublicService::create([
            'category_id' => $cat->category_id,
            'title' => 'Surat Keterangan Usaha (SKU)',
            'slug' => 'sku',
            'category_badge' => 'SURAT_PENGANTAR',
            'description' => 'Surat resmi legalitas usaha mikro/kecil warga',
            'sla_duration' => '1 Hari Kerja',
            'cost_info' => 'Gratis',
            'requirements' => ['KTP', 'KK', 'Pengantar RT'],
            'steps' => [['step' => '1', 'name' => 'Loket', 'desc' => 'Daftar']],
            'is_active' => true,
        ]);

        Umkm::create([
            'category_id' => $cat->category_id,
            'reg_number' => 'MKD-001',
            'name' => 'Kopi Robusta Lereng Margodadi',
            'owner_name' => 'Pak Supardi',
            'phone' => '0812-3456-7890',
            'description' => 'Kopi robusta petik merah lereng Tanggamus',
            'is_active' => true,
        ]);

        Operator::create([
            'name' => 'Dewi Lestari',
            'email' => 'dewi@margodadi.desa.id',
            'password' => bcrypt('123'),
            'role' => 'OPERATOR',
            'is_active' => true,
        ]);
    }

    public function test_can_list_public_services(): void
    {
        $response = $this->getJson('/api/public/services');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data.services');
    }

    public function test_can_get_public_service_detail(): void
    {
        $response = $this->getJson('/api/public/services/sku');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.slug', 'sku');
    }

    public function test_can_list_umkms(): void
    {
        $response = $this->getJson('/api/public/umkms');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data.umkms');
    }

    public function test_can_init_chatbot_session(): void
    {
        $response = $this->postJson('/api/public/chatbot/init');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'data' => [
                    'session_id',
                    'anonymous_code',
                    'conversation_id',
                    'greeting' => ['sender', 'text'],
                ],
            ]);
    }

    public function test_can_send_chatbot_message_rag(): void
    {
        $response = $this->postJson('/api/public/chatbot/send', [
            'message' => 'Apa saja syarat membuat SKU?',
            'session_id' => 'SESS-TEST-001',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.message.sender', 'bot');
    }

    public function test_chatbot_triggers_hitl_escalation_when_human_requested(): void
    {
        $response = $this->postJson('/api/public/chatbot/send', [
            'message' => 'Saya butuh bantuan operator atau kadus segera',
            'session_id' => 'SESS-TEST-002',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.message.sender', 'operator')
            ->assertJsonPath('data.message.needsHuman', true);
    }

    public function test_can_submit_feedback(): void
    {
        $user = User::create(['anonymous_code' => 'WARGA-9999']);

        $conv = Conversation::create([
            'user_id' => $user->user_id,
            'channel' => 'WEB',
            'status' => 'RESOLVED',
            'needs_human' => false,
        ]);

        $response = $this->postJson('/api/public/chatbot/feedback', [
            'conversation_id' => $conv->conversation_id,
            'rating' => 5,
            'comment' => 'Sangat membantu dan cepat tanggap!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Terima kasih, umpan balik Anda berhasil disimpan.');
    }
}
