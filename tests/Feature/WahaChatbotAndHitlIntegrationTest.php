<?php

namespace Tests\Feature;

use App\Enums\ChannelType;
use App\Enums\ConversationStatus;
use App\Enums\OperatorRole;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Operator;
use App\Models\User;
use App\Services\RagGatewayService;
use App\Services\WahaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class WahaChatbotAndHitlIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Operator $operator;
    private string $operatorToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = Operator::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'operator@margodadi.desa.id',
            'role' => OperatorRole::OPERATOR,
            'is_active' => true,
        ]);

        $this->operatorToken = $this->operator->createToken('test_token')->plainTextToken;
    }

    public function test_waha_webhook_ignores_from_me_and_non_message_events(): void
    {
        $response = $this->postJson('/api/webhook/waha', [
            'event' => 'session.status',
            'payload' => ['status' => 'WORKING'],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'ignored');

        $fromMeResponse = $this->postJson('/api/webhook/waha', [
            'event' => 'message',
            'payload' => [
                'fromMe' => true,
                'from' => '628123456789@c.us',
                'body' => 'Pesan terkirim',
            ],
        ]);

        $fromMeResponse->assertStatus(200)
            ->assertJsonPath('status', 'ignored');
    }

    public function test_waha_message_triggers_ai_rag_response(): void
    {
        $this->mock(WahaService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendSeen')->andReturn(true);
            $mock->shouldReceive('startTyping')->andReturn(true);
            $mock->shouldReceive('stopTyping')->andReturn(true);
            $mock->shouldReceive('sendText')
                ->once()
                ->with(
                    '628123456789@c.us',
                    \Mockery::on(fn ($text) => str_contains($text, 'Syarat SKU adalah KTP dan KK'))
                )
                ->andReturn(['id' => 'true_msg_123']);
        });

        $this->mock(RagGatewayService::class, function (MockInterface $mock) {
            $mock->shouldReceive('chat')
                ->once()
                ->with('Apa syarat membuat SKU?', 3, \Mockery::on(fn ($session) => str_starts_with($session, 'whatsapp:')))
                ->andReturn([
                    'question' => 'Apa syarat membuat SKU?',
                    'answer' => 'Syarat SKU adalah KTP dan KK asli serta surat RT.',
                    'sources' => [],
                ]);
        });

        $response = $this->postJson('/api/webhook/waha', [
            'event' => 'message',
            'payload' => [
                'id' => 'msg_waha_001',
                'timestamp' => time(),
                'from' => '628123456789@c.us',
                'fromMe' => false,
                'body' => 'Apa syarat membuat SKU?',
                '_data' => ['notifyName' => 'Ahmad Warga'],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('mode', 'AI');

        $this->assertDatabaseHas('conversations', [
            'channel' => ChannelType::WHATSAPP->value,
            'external_conversation_id' => '628123456789@c.us',
            'needs_human' => false,
        ]);

        $this->assertDatabaseHas('messages', [
            'content' => 'Apa syarat membuat SKU?',
        ]);
    }

    public function test_waha_auto_escalation_to_hitl_when_operator_requested(): void
    {
        $this->mock(WahaService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendSeen')->andReturn(true);
            $mock->shouldReceive('sendText')
                ->once()
                ->with(
                    '628123456789@c.us',
                    \Mockery::on(fn ($text) => str_contains($text, 'Petugas Pelayanan Pekon Margodadi'))
                )
                ->andReturn(['id' => 'true_msg_handoff']);
        });

        $response = $this->postJson('/api/webhook/waha', [
            'event' => 'message',
            'payload' => [
                'id' => 'msg_waha_002',
                'timestamp' => time(),
                'from' => '628123456789@c.us',
                'fromMe' => false,
                'body' => 'Tolong saya ingin bicara langsung dengan operator pekon',
                '_data' => ['notifyName' => 'Ibu Siti'],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('mode', 'OPERATOR')
            ->assertJsonPath('escalated', true);

        $this->assertDatabaseHas('conversations', [
            'channel' => ChannelType::WHATSAPP->value,
            'external_conversation_id' => '628123456789@c.us',
            'needs_human' => true,
            'status' => ConversationStatus::ASSIGNED->value,
        ]);

        $this->assertDatabaseHas('hitl_events', [
            'event_type' => 'NEED_HUMAN',
        ]);
    }

    public function test_waha_message_queued_when_operator_mode_is_active(): void
    {
        $user = User::factory()->create(['phone_number' => '628123456789']);
        $conv = Conversation::create([
            'user_id' => $user->user_id,
            'external_conversation_id' => '628123456789@c.us',
            'channel' => ChannelType::WHATSAPP,
            'status' => ConversationStatus::ASSIGNED,
            'needs_human' => true,
            'assigned_operator_id' => $this->operator->operator_id,
        ]);

        $this->mock(WahaService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendSeen')->andReturn(true);
            // Must NOT call sendText (bot is muted/paused)
            $mock->shouldNotReceive('sendText');
        });

        $response = $this->postJson('/api/webhook/waha', [
            'event' => 'message',
            'payload' => [
                'id' => 'msg_waha_003',
                'timestamp' => time(),
                'from' => '628123456789@c.us',
                'fromMe' => false,
                'body' => 'Baik pak saya tunggu responnya',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('mode', 'OPERATOR');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conv->conversation_id,
            'content' => 'Baik pak saya tunggu responnya',
        ]);
    }

    public function test_operator_can_toggle_intervention_mode_to_operator_and_back_to_ai(): void
    {
        $user = User::factory()->create();
        $conv = Conversation::create([
            'user_id' => $user->user_id,
            'external_conversation_id' => '628123456789@c.us',
            'channel' => ChannelType::WHATSAPP,
            'status' => ConversationStatus::OPEN,
            'needs_human' => false,
        ]);

        $this->mock(WahaService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendText')
                ->twice()
                ->andReturn(['id' => 'mock_notice_id']);
        });

        // 1. Operator takes over (Mode: OPERATOR)
        $takeoverResponse = $this->withHeader('Authorization', 'Bearer ' . $this->operatorToken)
            ->patchJson("/api/admin/conversations/{$conv->conversation_id}/mode", [
                'mode' => 'OPERATOR',
                'notes' => 'Saya ambil alih penanganan keluhan',
                'notify_citizen' => true,
            ]);

        $takeoverResponse->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.active_mode', 'OPERATOR')
            ->assertJsonPath('data.needs_human', true);

        $this->assertDatabaseHas('conversations', [
            'conversation_id' => $conv->conversation_id,
            'needs_human' => true,
            'status' => ConversationStatus::ASSIGNED->value,
        ]);

        $this->assertDatabaseHas('hitl_events', [
            'conversation_id' => $conv->conversation_id,
            'event_type' => 'TAKE_OVER',
        ]);

        // 2. Operator switches back to AI (Mode: AI)
        $resumeAiResponse = $this->withHeader('Authorization', 'Bearer ' . $this->operatorToken)
            ->patchJson("/api/admin/conversations/{$conv->conversation_id}/mode", [
                'mode' => 'AI',
                'notes' => 'Urusan selesai, aktifkan bot AI kembali',
                'notify_citizen' => true,
            ]);

        $resumeAiResponse->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.active_mode', 'AI')
            ->assertJsonPath('data.needs_human', false);

        $this->assertDatabaseHas('conversations', [
            'conversation_id' => $conv->conversation_id,
            'needs_human' => false,
            'status' => ConversationStatus::OPEN->value,
        ]);
    }

    public function test_operator_reply_is_delivered_to_whatsapp(): void
    {
        $user = User::factory()->create();
        $conv = Conversation::create([
            'user_id' => $user->user_id,
            'external_conversation_id' => '628999888777@c.us',
            'channel' => ChannelType::WHATSAPP,
            'status' => ConversationStatus::ASSIGNED,
            'needs_human' => true,
        ]);

        $this->mock(WahaService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendText')
                ->once()
                ->with(
                    '628999888777@c.us',
                    \Mockery::on(fn ($text) => str_contains($text, 'Silakan bawa fotokopi KTP besok'))
                )
                ->andReturn(['id' => 'op_reply_123']);
        });

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->operatorToken)
            ->postJson("/api/admin/conversations/{$conv->conversation_id}/reply", [
                'content' => 'Silakan bawa fotokopi KTP besok ke loket kantor desa.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conv->conversation_id,
            'content' => 'Silakan bawa fotokopi KTP besok ke loket kantor desa.',
            'operator_id' => $this->operator->operator_id,
        ]);

        $this->assertDatabaseHas('hitl_events', [
            'conversation_id' => $conv->conversation_id,
            'event_type' => 'OPERATOR_RESPONSE',
        ]);
    }
}
