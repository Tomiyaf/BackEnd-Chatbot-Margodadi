<?php

namespace Tests\Feature;

use App\Enums\ChannelType;
use App\Enums\ConversationStatus;
use App\Enums\HitlEventType;
use App\Enums\OperatorRole;
use App\Enums\OperatorStatus;
use App\Enums\PriorityLevel;
use App\Enums\ResearchSessionStatus;
use App\Enums\SenderType;
use App\Enums\ServiceDomain;
use App\Models\ActivityLog;
use App\Models\Conversation;
use App\Models\ConversationAssignment;
use App\Models\EducationExposure;
use App\Models\EducationTopic;
use App\Models\Feedback;
use App\Models\HitlEvent;
use App\Models\KbChunk;
use App\Models\KbDocument;
use App\Models\Message;
use App\Models\Operator;
use App\Models\PublicService;
use App\Models\RagConfiguration;
use App\Models\RagInteraction;
use App\Models\RagRetrieval;
use App\Models\ResearchSession;
use App\Models\ServiceCategory;
use App\Models\SystemLog;
use App\Models\Umkm;
use App\Models\UmkmProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_schema_entities_and_relationships(): void
    {
        // 1. User
        $user = User::create([
            'anonymous_code' => 'ANON-001',
            'phone_number' => '081234567890',
        ]);
        $this->assertNotNull($user->user_id);

        // 2. Service Category
        $category = ServiceCategory::create([
            'name' => 'Layanan Kependudukan',
            'slug' => 'layanan-kependudukan',
            'domain' => ServiceDomain::PUBLIC_SERVICE,
            'icon' => 'id-card',
            'description' => 'Layanan terkait KTP, KK, dan Surat Keterangan',
            'is_active' => true,
        ]);
        $this->assertNotNull($category->category_id);

        // 3. Public Service
        $service = PublicService::create([
            'category_id' => $category->category_id,
            'title' => 'Surat Keterangan Usaha (SKU)',
            'slug' => 'surat-keterangan-usaha',
            'category_badge' => 'SURAT_PENGANTAR',
            'sub_category_badge' => 'Administrasi',
            'description' => 'Pengurusan surat pengantar SKU untuk perbankan / KUR',
            'requirements' => ['FC KTP', 'FC KK', 'Surat Pengantar RT'],
            'steps' => [['step' => 1, 'name' => 'Pengajuan berkas']],
            'sla_duration' => '1 Hari Kerja',
            'cost_info' => 'Gratis (Rp 0)',
            'officer_in_charge' => 'Loket Pelayanan',
            'tags' => ['sku', 'kur', 'usaha'],
            'is_active' => true,
        ]);
        $this->assertEquals($category->category_id, $service->category->category_id);

        // 4. Operator
        $operator = Operator::create([
            'name' => 'Aparatur Desa Margodadi',
            'email' => 'admin@margodadi.desa.id',
            'password' => bcrypt('password123'),
            'role' => OperatorRole::ADMIN,
            'status' => OperatorStatus::ONLINE,
            'is_active' => true,
        ]);
        $this->assertNotNull($operator->operator_id);

        // 5. Conversation
        $conversation = Conversation::create([
            'user_id' => $user->user_id,
            'category_id' => $category->category_id,
            'assigned_operator_id' => $operator->operator_id,
            'channel' => ChannelType::WEB,
            'status' => ConversationStatus::OPEN,
            'priority' => PriorityLevel::MEDIUM,
            'needs_human' => false,
            'citizen_name' => 'Warga Anonim',
            'external_conversation_id' => 'EXT-12345',
            'started_at' => now(),
        ]);
        $this->assertEquals($user->user_id, $conversation->user->user_id);
        $this->assertEquals($operator->operator_id, $conversation->assignedOperator->operator_id);

        // 6. Message
        $message = Message::create([
            'conversation_id' => $conversation->conversation_id,
            'operator_id' => null,
            'sender_type' => SenderType::USER,
            'content' => 'Halo, bagaimana syarat buat SKU?',
            'metadata' => ['intent' => 'public_service_sku'],
            'created_at' => now(),
        ]);
        $this->assertEquals($conversation->conversation_id, $message->conversation->conversation_id);

        // 7. UMKM
        $umkm = Umkm::create([
            'category_id' => $category->category_id,
            'reg_number' => 'MKD-UMKM-001',
            'name' => 'Keripik Pisang Margodadi Jaya',
            'owner_name' => 'Pak Budi',
            'phone' => '0812334455',
            'wa_number' => '62812334455',
            'description' => 'Produksi keripik pisang aneka rasa khas desa',
            'gallery_urls' => ['https://example.com/img1.jpg'],
            'is_active' => true,
        ]);
        $this->assertNotNull($umkm->umkm_id);

        // 8. UMKM Product
        $product = UmkmProduct::create([
            'umkm_id' => $umkm->umkm_id,
            'name' => 'Keripik Pisang Coklat Lumer',
            'price' => 'Rp 15.000',
            'is_active' => true,
        ]);
        $this->assertEquals($umkm->umkm_id, $product->umkm->umkm_id);

        // 9. KB Document
        $kbDoc = KbDocument::create([
            'title' => 'SOP Pelayanan Administrasi Desa',
            'domain' => ServiceDomain::PUBLIC_SERVICE,
            'source' => 'Peraturan Desa Margodadi No. 04/2024',
            'validator' => 'Sekretaris Desa',
            'version' => 'v1.0.0',
            'is_active' => true,
        ]);
        $this->assertNotNull($kbDoc->document_id);

        // 10. KB Chunk
        $kbChunk = KbChunk::create([
            'document_id' => $kbDoc->document_id,
            'chunk_index' => 0,
            'content' => 'Syarat pembuatan SKU: 1. KTP, 2. KK, 3. Pengantar RT',
            'metadata' => ['section' => 'Persyaratan SKU'],
            'created_at' => now(),
        ]);
        $this->assertEquals($kbDoc->document_id, $kbChunk->document->document_id);

        // 11. RAG Configuration
        $ragConfig = RagConfiguration::create([
            'version' => 'v1-openai-text-embedding-3',
            'embedding_model' => 'text-embedding-3-small',
            'llm_model' => 'gpt-4o-mini',
            'chunking_config' => ['size' => 500, 'overlap' => 50],
            'is_active' => true,
            'created_at' => now(),
        ]);
        $this->assertNotNull($ragConfig->rag_config_id);

        // 12. RAG Interaction
        $ragInteraction = RagInteraction::create([
            'message_id' => $message->message_id,
            'rag_config_id' => $ragConfig->rag_config_id,
            'query' => 'syarat buat SKU',
            'response' => 'Syarat pembuatan SKU adalah membawa FC KTP, FC KK, dan pengantar RT.',
            'status' => 'SUCCESS',
            'latency_ms' => 450,
            'created_at' => now(),
        ]);
        $this->assertEquals($message->message_id, $ragInteraction->message->message_id);

        // 13. RAG Retrieval
        $ragRetrieval = RagRetrieval::create([
            'rag_interaction_id' => $ragInteraction->rag_interaction_id,
            'chunk_id' => $kbChunk->chunk_id,
            'rank' => 1,
            'similarity_score' => 0.895123,
            'created_at' => now(),
        ]);
        $this->assertEquals($kbChunk->chunk_id, $ragRetrieval->chunk->chunk_id);

        // 14. Conversation Assignment
        $assignment = ConversationAssignment::create([
            'conversation_id' => $conversation->conversation_id,
            'operator_id' => $operator->operator_id,
            'assigned_at' => now(),
            'created_at' => now(),
        ]);
        $this->assertEquals($operator->operator_id, $assignment->operator->operator_id);

        // 15. HITL Event
        $hitlEvent = HitlEvent::create([
            'conversation_id' => $conversation->conversation_id,
            'operator_id' => $operator->operator_id,
            'event_type' => HitlEventType::OPERATOR_ASSIGNED,
            'notes' => 'Operator mengambil alih percakapan',
            'created_at' => now(),
        ]);
        $this->assertEquals(HitlEventType::OPERATOR_ASSIGNED, $hitlEvent->event_type);

        // 16. Feedback
        $feedback = Feedback::create([
            'conversation_id' => $conversation->conversation_id,
            'message_id' => $message->message_id,
            'user_id' => $user->user_id,
            'rating' => 5,
            'comment' => 'Sangat membantu dan informatif!',
            'created_at' => now(),
        ]);
        $this->assertEquals(5, $feedback->rating);

        // 17. Research Session
        $session = ResearchSession::create([
            'user_id' => $user->user_id,
            'conversation_id' => $conversation->conversation_id,
            'anonymous_code' => 'ANON-001',
            'education_version' => 'v1.2',
            'quiz_score' => 90.00,
            'status' => ResearchSessionStatus::COMPLETED,
            'started_at' => now(),
            'completed_at' => now(),
            'created_at' => now(),
        ]);
        $this->assertEquals(ResearchSessionStatus::COMPLETED, $session->status);

        // 18. Education Topic
        $topic = EducationTopic::create([
            'name' => 'Edukasi Pengelolaan Sampah Organik',
            'slug' => 'edukasi-pengelolaan-sampah-organik',
            'description' => 'Materi pemilahan sampah organik dan komposting rumah tangga',
            'knowledge_base_version' => 'v1.0',
            'sequence_order' => 1,
            'is_active' => true,
        ]);
        $this->assertNotNull($topic->topic_id);

        // 19. Education Exposure
        $exposure = EducationExposure::create([
            'research_session_id' => $session->research_session_id,
            'topic_id' => $topic->topic_id,
            'interaction_count' => 3,
            'quiz_score' => 95.00,
            'started_at' => now(),
            'completed_at' => now(),
            'created_at' => now(),
        ]);
        $this->assertEquals($topic->topic_id, $exposure->topic->topic_id);

        // 20. Activity Log
        $activityLog = ActivityLog::create([
            'operator_id' => $operator->operator_id,
            'actor_name' => $operator->name,
            'action' => 'OPERATOR_RESPONSE',
            'target' => '#CV-'.$conversation->conversation_id,
            'description' => 'Operator membalas pesan warga',
            'channel' => ChannelType::WEB,
            'created_at' => now(),
        ]);
        $this->assertEquals($operator->operator_id, $activityLog->operator->operator_id);

        // 21. System Log
        $systemLog = SystemLog::create([
            'conversation_id' => $conversation->conversation_id,
            'message_id' => $message->message_id,
            'service' => 'RAG_ENGINE',
            'event_type' => 'RETRIEVAL_SUCCESS',
            'status' => '200',
            'latency_ms' => 120,
            'metadata' => ['chunks_retrieved' => 1],
            'created_at' => now(),
        ]);
        $this->assertEquals('RAG_ENGINE', $systemLog->service);
    }
}
