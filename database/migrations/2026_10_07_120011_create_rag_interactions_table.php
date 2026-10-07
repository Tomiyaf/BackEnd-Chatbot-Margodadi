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
        Schema::create('rag_interactions', function (Blueprint $table) {
            $table->id('rag_interaction_id');
            $table->foreignId('message_id')->constrained('messages', 'message_id')->cascadeOnDelete();
            $table->foreignId('rag_config_id')->nullable()->constrained('rag_configurations', 'rag_config_id')->nullOnDelete();

            $table->text('query');
            $table->text('response')->nullable();
            $table->string('status', 50)->nullable();
            $table->integer('latency_ms')->nullable();

            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['message_id', 'rag_config_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rag_interactions');
    }
};
