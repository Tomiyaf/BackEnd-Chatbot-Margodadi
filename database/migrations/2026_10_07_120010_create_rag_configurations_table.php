<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rag_configurations', function (Blueprint $table) {
            $table->id('rag_config_id');
            $table->string('version', 50)->unique();
            $table->string('embedding_model', 150)->nullable();
            $table->string('llm_model', 150)->nullable();
            $table->jsonb('chunking_config')->nullable();
            $table->jsonb('retrieval_config')->nullable();
            $table->jsonb('generation_config')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rag_configurations');
    }
};
