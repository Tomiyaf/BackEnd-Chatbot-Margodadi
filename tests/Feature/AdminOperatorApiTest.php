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
use Illuminate\Support\Facades\Hash;
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
            'username' => 'superadmin',
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
            'username' => 'operator.uji',
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
                        'username',
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
        $this->assertEquals('operator.uji', $target['username']);
        $this->assertEquals(1, $target['assignedCount']);
        $this->assertEquals(1, $target['resolvedCount']);
    }

    public function test_can_check_availability(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/operators/check-availability?username=superadmin&email=admin@margodadi.desa.id');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'username_available' => false,
                    'email_available' => false,
                ],
            ]);

        // Test with unused username and email
        $responseUnused = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/admin/operators/check-availability?username=brand_new_user&email=new@desa.id');

        $responseUnused->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'username_available' => true,
                    'email_available' => true,
                ],
            ]);
    }

    public function test_can_create_operator_with_username_and_email(): void
    {
        $payload = [
            'name' => 'Bambang Kusumo',
            'username' => 'bambang.k',
            'email' => 'bambang@margodadi.desa.id',
            'password' => 'secret123',
            'phone' => '081299998888',
            'role' => 'OPERATOR',
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/operators', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'name' => 'Bambang Kusumo',
                    'username' => 'bambang.k',
                    'email' => 'bambang@margodadi.desa.id',
                ],
            ]);

        $this->assertDatabaseHas('operators', [
            'username' => 'bambang.k',
            'email' => 'bambang@margodadi.desa.id',
        ]);
    }

    public function test_cannot_create_operator_with_duplicate_username_or_email(): void
    {
        $payload = [
            'name' => 'Another Admin',
            'username' => 'superadmin', // Duplicate!
            'email' => 'admin@margodadi.desa.id', // Duplicate!
            'password' => 'secret123',
            'role' => 'ADMIN',
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/admin/operators', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username', 'email']);
    }

    public function test_can_update_operator_and_reset_password(): void
    {
        $op = Operator::factory()->create([
            'username' => 'dewi.lestari',
            'email' => 'dewi@margodadi.desa.id',
            'password' => Hash::make('oldpassword'),
            'role' => OperatorRole::OPERATOR,
        ]);

        $updatePayload = [
            'name' => 'Dewi Lestari M.Si',
            'username' => 'dewi.lestari',
            'email' => 'dewi.lestari@margodadi.desa.id',
            'password' => 'newpassword123', // Reset password
            'phone' => '081300001111',
            'role' => 'OPERATOR',
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/admin/operators/'.$op->operator_id, $updatePayload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $op->refresh();
        $this->assertEquals('Dewi Lestari M.Si', $op->name);
        $this->assertEquals('dewi.lestari@margodadi.desa.id', $op->email);
        $this->assertTrue(Hash::check('newpassword123', $op->password));
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/operators/'.$this->admin->operator_id);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri saat sedang aktif.',
            ]);

        $this->assertDatabaseHas('operators', [
            'operator_id' => $this->admin->operator_id,
        ]);
    }

    public function test_admin_can_delete_operator(): void
    {
        $op = Operator::factory()->create([
            'username' => 'target.delete',
            'email' => 'target@margodadi.desa.id',
            'role' => OperatorRole::OPERATOR,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/admin/operators/'.$op->operator_id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseMissing('operators', [
            'operator_id' => $op->operator_id,
        ]);
    }
}
