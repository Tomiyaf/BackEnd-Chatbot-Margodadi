<?php

namespace Tests\Feature;

use App\Enums\OperatorRole;
use App\Models\Operator;
use Database\Seeders\OperatorSeeder;
use Database\Seeders\ServiceCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAnalyticsApiTest extends TestCase
{
    use RefreshDatabase;

    protected Operator $admin;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            OperatorSeeder::class,
            ServiceCategorySeeder::class,
        ]);

        $this->admin = Operator::where('email', 'admin@margodadi.desa.id')->first()
            ?? Operator::where('role', OperatorRole::ADMIN)->first();
        $this->token = $this->admin->createToken('admin_test_token')->plainTextToken;
    }

    public function test_can_get_analytics_overview(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/admin/analytics/overview?period=MONTH');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'period',
                    'kpi' => [
                        'totalConversations',
                        'autoResponseRatio',
                        'humanInterventionRatio',
                        'avgOperatorResponseTimeMinutes',
                    ],
                    'trendDays',
                    'categories',
                    'operators',
                ],
            ]);
    }
}
