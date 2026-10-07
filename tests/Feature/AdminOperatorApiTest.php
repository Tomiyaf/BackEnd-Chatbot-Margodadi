<?php

namespace Tests\Feature;

use App\Enums\ChannelType;
use App\Enums\ConversationStatus;
use App\Enums\OperatorRole;
use App\Enums\OperatorStatus;
use App\Enums\PriorityLevel;
use App\Models\Conversation;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOperatorApiTest extends TestCase
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
            'status' => OperatorStatus::ONLINE,
        ]);
        $this->token = $this->admin->createToken('test_token')->plainTextToken;
    }

    public function test_can_fetch_operators_with_counts(): void
    {
        $op = Operator::factory()->create([
            'name' => 'Operator Uji',
            'email' => 'uji@margodadi.desa.id',
            'role' => OperatorRole::OPERATOR,
            'status' => OperatorStatus::ONLINE,
        ]);

        $user = User::factory()->create();

        // Active conversation
        Conversation::create([
            'user_id' => $user->user_id,
            'assigned_operator_id' => $op->operator_id,
            'channel' => ChannelType::WHATSAPP,
            'status' => ConversationStatus::ASSIGNED,
            'priority' => PriorityLevel::HIGH,
            'needs_human' => true,
            'started_at' => now(),
        ]);

        // Resolved conversation
        Conversation::create([
            'user_id' => $user->user_id,
            'assigned_operator_id' => $op->operator_id,
            'channel' => ChannelType::WEB,
            'status' => ConversationStatus::RESOLVED,
            'priority' => PriorityLevel::LOW,
            'needs_human' => false,
            'started_at' => now()->subHour(),
            'resolved_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/operators');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'operator_id',
                        'name',
                        'email',
                        'role',
                        'status',
                        'avatar',
                        'assignedCount',
                        'resolvedCount',
                        'avgResponseTime',
                    ],
                ],
            ]);

        $data = collect($response->json('data'));
        $target = $data->firstWhere('email', 'uji@margodadi.desa.id');

        $this->assertNotNull($target);
        $this->assertEquals(1, $target['assignedCount']);
        $this->assertEquals(1, $target['resolvedCount']);
    }
}
