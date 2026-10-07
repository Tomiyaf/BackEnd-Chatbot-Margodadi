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
        Schema::create('rag_retrievals', function (Blueprint $table) {
            $table->id('retrieval_id');
            $table->foreignId('rag_interaction_id')->constrained('rag_interactions', 'rag_interaction_id')->cascadeOnDelete();
            $table->foreignId('chunk_id')->constrained('kb_chunks', 'chunk_id')->cascadeOnDelete();

            $table->integer('rank')->nullable();
            $table->decimal('similarity_score', 10, 6)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['rag_interaction_id', 'chunk_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rag_retrievals');
    }
};
