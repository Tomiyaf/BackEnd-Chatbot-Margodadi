<?php

namespace Tests\Feature;

use App\Enums\OperatorRole;
use App\Enums\ServiceDomain;
use App\Models\KbChunk;
use App\Models\KbDocument;
use App\Models\Operator;
use Database\Seeders\KbDocumentSeeder;
use Database\Seeders\OperatorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeBaseApiTest extends TestCase
{
    use RefreshDatabase;

    protected Operator $admin;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            OperatorSeeder::class,
            KbDocumentSeeder::class,
        ]);

        $this->admin = Operator::where('email', 'admin@margodadi.desa.id')->first()
            ?? Operator::where('role', OperatorRole::ADMIN)->first();
        $this->token = $this->admin->createToken('admin_test_token')->plainTextToken;
    }

    public function test_can_get_knowledge_base_stats(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/admin/knowledge-base/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'total_documents',
                    'active_documents',
                    'total_chunks',
                    'avg_chunks_per_doc',
                    'domain_breakdown',
                    'embedding_model',
                    'chunk_size',
                ],
            ]);
    }

    public function test_can_list_knowledge_documents_with_chunks_count(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/admin/knowledge-base/documents');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'document_id',
                        'doc_code',
                        'title',
                        'domain',
                        'chunks_count',
                        'is_active',
                    ],
                ],
                'meta',
            ]);

        $this->assertNotEmpty($response->json('data'));
    }

    public function test_can_create_document_with_auto_chunking(): void
    {
        $payload = [
            'title' => 'SOP Surat Keterangan Domisili Usaha Margodadi',
            'domain' => 'PUBLIC_SERVICE',
            'source' => 'Perdes No. 05/2026',
            'validator' => 'Kasi Pelayanan',
            'version' => 'v1.0',
            'is_active' => true,
            'content' => 'Pengurusan Surat Keterangan Domisili Usaha memerlukan fotokopi KTP dan KK pemohon, surat pengantar RT, serta surat sewa tempat usaha jika mengontrak. Pelayanan gratis dan selesai dalam 1 hari kerja.',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/admin/knowledge-base/documents', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
            ]);

        $docId = $response->json('data.document_id');
        $this->assertDatabaseHas('kb_documents', [
            'document_id' => $docId,
            'title' => 'SOP Surat Keterangan Domisili Usaha Margodadi',
        ]);

        $this->assertDatabaseHas('kb_chunks', [
            'document_id' => $docId,
        ]);
    }

    public function test_can_test_semantic_retrieval_simulator(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/admin/knowledge-base/test-retrieval', [
                'query' => 'Surat Keterangan Usaha SKU',
                'top_k' => 3,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'query',
                    'top_k',
                    'results' => [
                        '*' => [
                            'chunk_id',
                            'document_title',
                            'similarity',
                            'similarity_percentage',
                            'content',
                        ],
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data.results'));
    }
}
