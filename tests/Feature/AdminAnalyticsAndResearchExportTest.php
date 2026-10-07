<?php

namespace Tests\Feature;

use App\Enums\OperatorRole;
use App\Models\Conversation;
use App\Models\EducationExposure;
use App\Models\EducationTopic;
use App\Models\Operator;
use App\Models\ResearchSession;
use App\Models\ServiceCategory;
use App\Models\User;
use Database\Seeders\EducationSeeder;
use Database\Seeders\OperatorSeeder;
use Database\Seeders\ServiceCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAnalyticsAndResearchExportTest extends TestCase
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
            EducationSeeder::class,
        ]);

        $this->admin = Operator::where('email', 'admin@margodadi.desa.id')->first()
            ?? Operator::where('role', OperatorRole::ADMIN)->first();
        $this->token = $this->admin->createToken('admin_test_token')->plainTextToken;
    }

    public function test_can_list_education_sessions_with_stats(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/admin/education/sessions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'sessions' => [
                        '*' => [
                            'id',
                            'sessionId',
                            'respondentCode',
                            'topic',
                            'channel',
                            'interactionCount',
                            'quizScore',
                            'status',
                            'materialVersion',
                            'startedAt',
                        ],
                    ],
                    'stats' => [
                        'totalSessions',
                        'completedSessions',
                        'inProgressSessions',
                        'completionRate',
                        'popularTopic',
                        'topicBreakdown',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data.sessions'));
    }

    public function test_can_filter_education_sessions_by_status(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/admin/education/sessions?status=COMPLETED');

        $response->assertStatus(200);
        $sessions = $response->json('data.sessions');
        foreach ($sessions as $session) {
            $this->assertEquals('COMPLETED', $session['status']);
        }
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

    public function test_can_preview_research_dataset(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/admin/research/preview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'respondentCode',
                        'sessionId',
                        'topic',
                        'interactionCount',
                        'status',
                        'quizScore',
                        'materialVersion',
                        'startedAt',
                    ],
                ],
            ]);
    }

    public function test_can_stream_export_research_csv(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->get('/api/admin/research/export-csv?period=ALL&fields=respondentCode,topic,status');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment; filename="research_dataset_margodadi_', $response->headers->get('content-disposition'));
    }
}
