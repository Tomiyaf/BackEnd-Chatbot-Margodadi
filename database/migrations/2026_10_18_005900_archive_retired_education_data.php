<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Must precede 2026_10_18_010000, which removes the three legacy tables.
        if (! Schema::hasTable('retired_education_archive')) {
            Schema::create('retired_education_archive', function (Blueprint $table) {
                $table->id();
                $table->string('source_table', 80);
                $table->string('source_key', 80);
                $table->json('payload');
                $table->timestamp('archived_at');
                $table->unique(['source_table', 'source_key']);
            });
        }

        foreach ([
            'research_sessions' => 'research_session_id',
            'education_topics' => 'topic_id',
            'education_exposures' => 'exposure_id',
            'kb_documents' => 'document_id',
            'kb_chunks' => 'chunk_id',
            'service_categories' => 'category_id',
        ] as $table => $key) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $query = DB::table($table);
            if (in_array($table, ['kb_documents', 'service_categories'], true)) {
                $query->where('domain', 'WASTE_EDUCATION');
            } elseif ($table === 'kb_chunks') {
                $query->whereIn('document_id', DB::table('kb_documents')
                    ->select('document_id')->where('domain', 'WASTE_EDUCATION'));
            }

            $query->chunkById(100, function ($rows) use ($table, $key) {
                foreach ($rows as $row) {
                    $identity = ['source_table' => $table, 'source_key' => (string) $row->{$key}];
                    // Preserve the first snapshot, including the original active status.
                    if (! DB::table('retired_education_archive')->where($identity)->exists()) {
                        DB::table('retired_education_archive')->insert($identity + [
                            'payload' => json_encode($row, JSON_THROW_ON_ERROR),
                            'archived_at' => now(),
                        ]);
                    }
                }
            }, $key);
        }

        // Original knowledge records remain stored; model scopes hide the retired domain.
        foreach (['kb_documents', 'service_categories'] as $table) {
            DB::table($table)->where('domain', 'WASTE_EDUCATION')->update(['is_active' => false]);
        }
    }

    public function down(): void
    {
        // Intentionally retain archival snapshots on rollback to avoid data loss.
    }
};
