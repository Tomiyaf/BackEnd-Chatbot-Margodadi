<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $isPgsql = DB::getDriverName() === 'pgsql';

        if ($isPgsql) {
            try {
                DB::statement('CREATE EXTENSION IF NOT EXISTS vector;');
            } catch (\Throwable $e) {
                // Extension may require superuser or pgvector package installed in PostgreSQL
            }
        }

        Schema::create('kb_chunks', function (Blueprint $table) use ($isPgsql) {
            $table->id('chunk_id');
            $table->foreignId('document_id')->constrained('kb_documents', 'document_id')->cascadeOnDelete();
            $table->integer('chunk_index');
            $table->text('content');

            if ($isPgsql) {
                try {
                    $table->addColumn('vector', 'embedding')->nullable();
                } catch (\Throwable $e) {
                    $table->text('embedding')->nullable();
                }
            } else {
                $table->text('embedding')->nullable();
            }

            $table->jsonb('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['document_id', 'chunk_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kb_chunks');
    }
};
