<?php

namespace Tests\Feature;

use App\Enums\ChannelType;
use App\Enums\ConversationStatus;
use App\Enums\OperatorRole;
use App\Enums\PriorityLevel;
use App\Models\Conversation;
use App\Models\Operator;
use App\Models\RagConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardAndSettingsApiTest extends TestCase
{
    use RefreshDatabase;

    private Operator $admin;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Operator::factory()->create([
            'email' => 'admin@margodadi.desa.id',
            'role' => OperatorRole::ADMIN,
        ]);
        $this->token = $this->admin->createToken('test_token')->plainTextToken;
    }

    public function test_can_fetch_dashboard_stats(): void
    {
        $user = User::factory()->create();

        Conversation::create([
            'user_id' => $user->user_id,
            'channel' => ChannelType::WHATSAPP,
            'status' => ConversationStatus::OPEN,
            'needs_human' => true,
            'priority' => PriorityLevel::URGENT,
            'citizen_name' => 'Warga Test',
            'started_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/dashboard/stats?period=MONTH');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'kpi' => [
                        'total_conversations',
                        'active_conversations',
                        'need_human_count',
                        'assigned_count',
                        'online_operators',
                    ],
                    'channels',
                    'categories',
                    'recent_hitl',
                    'recent_activities',
                ],
            ]);

        $this->assertEquals(1, $response->json('data.kpi.total_conversations'));
        $this->assertEquals(1, $response->json('data.kpi.need_human_count'));
    }

    public function test_can_get_and_update_rag_settings(): void
    {
        // 1. Get default RAG configuration
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/settings/rag');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'version',
                    'embedding_model',
                    'llm_model',
                ],
            ]);

        // 2. Update RAG configuration
        $updateResponse = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/settings/rag', [
                'embedding_model' => 'text-embedding-3-large',
                'llm_model' => 'gpt-4o',
                'chunking_config' => ['size' => 600, 'overlap' => 60],
            ]);

        $updateResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'embedding_model' => 'text-embedding-3-large',
                    'llm_model' => 'gpt-4o',
                ],
            ]);

        $this->assertDatabaseHas('rag_configurations', [
            'llm_model' => 'gpt-4o',
        ]);
    }
}
