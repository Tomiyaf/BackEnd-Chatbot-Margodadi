<?php

namespace Tests\Feature;

use App\Models\KbDocument;
use App\Models\Operator;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\VectorChunkingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RetiredEducationDataTest extends TestCase
{
    use RefreshDatabase;

    private function legacyDocument(): int
    {
        return DB::table('kb_documents')->insertGetId([
            'title' => 'Materi sampah lama', 'domain' => 'WASTE_EDUCATION',
            'source' => 'Arsip lokal', 'version' => 'v1',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ], 'document_id');
    }

    public function test_legacy_rows_are_hidden_without_deleting_or_casting_them(): void
    {
        Sanctum::actingAs(Operator::factory()->create(['role' => 'ADMIN', 'is_active' => true]));
        Http::fake(['*' => Http::response([], 503)]);
        $legacyId = $this->legacyDocument();
        $legacyChunkId = DB::table('kb_chunks')->insertGetId([
            'document_id' => $legacyId, 'chunk_index' => 0,
            'content' => 'Materi sampah lama', 'metadata' => '{}', 'created_at' => now(),
        ], 'chunk_id');
        $categoryId = DB::table('service_categories')->insertGetId([
            'name' => 'Edukasi lama', 'slug' => 'edukasi-lama',
            'domain' => 'WASTE_EDUCATION', 'is_active' => true,
        ], 'category_id');
        KbDocument::create(['title' => 'SOP aktif', 'domain' => 'PUBLIC_SERVICE', 'source' => 'Uji', 'version' => 'v1', 'is_active' => true]);
        KbDocument::create(['title' => 'Direktori UMKM', 'domain' => 'UMKM', 'source' => 'Uji', 'version' => 'v1', 'is_active' => true]);

        $this->getJson('/api/admin/knowledge-base/documents')->assertOk()
            ->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 2);
        $this->getJson('/api/admin/knowledge-base/documents?domain=WASTE_EDUCATION')
            ->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/admin/knowledge-base/documents/'.$legacyId)->assertNotFound();
        $this->postJson('/api/admin/knowledge-base/documents/'.$legacyId.'/reindex')->assertNotFound();
        $this->putJson('/api/admin/knowledge-base/chunks/'.$legacyChunkId, ['content' => 'Tidak boleh diubah'])
            ->assertNotFound();
        $this->deleteJson('/api/admin/knowledge-base/chunks/'.$legacyChunkId)->assertNotFound();
        $this->getJson('/api/admin/knowledge-base/stats')->assertOk()
            ->assertJsonPath('data.total_documents', 2)->assertJsonPath('data.total_chunks', 0);
        $this->postJson('/api/admin/knowledge-base/documents', [
            'title' => 'Domain lama ditolak', 'domain' => 'WASTE_EDUCATION', 'content' => 'Materi lama',
        ])->assertUnprocessable()->assertJsonValidationErrors('domain');
        $this->assertNull(ServiceCategory::find($categoryId));
        $this->assertDatabaseHas('kb_documents', ['document_id' => $legacyId, 'is_active' => true]);
        $this->assertDatabaseHas('service_categories', ['category_id' => $categoryId]);
        $this->assertDatabaseHas('kb_chunks', ['chunk_id' => $legacyChunkId, 'content' => 'Materi sampah lama']);
        $this->assertSame(0, app(VectorChunkingService::class)->testRetrieval('sampah')['total_candidates']);
    }

    public function test_migration_archives_all_legacy_rows_before_their_tables_are_removed(): void
    {
        foreach (['120016_create_research_sessions', '120017_create_education_topics', '120018_create_education_exposures'] as $name) {
            (require database_path('migrations/2026_10_07_'.$name.'_table.php'))->up();
        }
        $user = User::create(['anonymous_code' => 'ARCHIVE-TEST']);
        $sessionId = DB::table('research_sessions')->insertGetId([
            'user_id' => $user->user_id, 'anonymous_code' => 'ARCHIVE-TEST', 'quiz_score' => 75.5,
        ], 'research_session_id');
        $topicId = DB::table('education_topics')->insertGetId(['name' => 'Topik lama'], 'topic_id');
        DB::table('education_exposures')->insert([
            'research_session_id' => $sessionId, 'topic_id' => $topicId, 'interaction_count' => 7,
        ]);
        $legacyId = $this->legacyDocument();
        DB::table('kb_chunks')->insert([
            'document_id' => $legacyId, 'chunk_index' => 0, 'content' => 'Isi arsip tetap utuh',
            'metadata' => '{"domain":"WASTE_EDUCATION"}', 'created_at' => now(),
        ]);
        DB::table('service_categories')->insert([
            'name' => 'Kategori lama', 'slug' => 'arsip-kategori', 'domain' => 'WASTE_EDUCATION', 'is_active' => true,
        ]);
        $active = KbDocument::create(['title' => 'Tetap aktif', 'domain' => 'PUBLIC_SERVICE', 'source' => 'Uji', 'version' => 'v1', 'is_active' => true]);
        $migration = require database_path('migrations/2026_10_18_005900_archive_retired_education_data.php');
        $migration->up();
        $migration->up();
        $this->assertDatabaseCount('retired_education_archive', 6);
        $snapshot = json_decode(DB::table('retired_education_archive')
            ->where('source_table', 'kb_documents')->value('payload'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertTrue((bool) $snapshot['is_active']);
        $this->assertSame('Materi sampah lama', $snapshot['title']);
        $this->assertDatabaseHas('kb_documents', ['document_id' => $legacyId, 'is_active' => false]);
        $this->assertDatabaseHas('kb_documents', ['document_id' => $active->document_id, 'is_active' => true]);
        (require database_path('migrations/2026_10_18_010000_drop_waste_education_and_research_tables.php'))->up();
        foreach (['research_sessions', 'education_topics', 'education_exposures'] as $table) {
            $this->assertFalse(Schema::hasTable($table));
            $this->assertSame(1, DB::table('retired_education_archive')->where('source_table', $table)->count());
        }
        $migration->down();
        $this->assertDatabaseCount('retired_education_archive', 6);
    }
}
