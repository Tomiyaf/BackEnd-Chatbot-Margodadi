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
        Schema::create('education_exposures', function (Blueprint $table) {
            $table->id('exposure_id');
            $table->foreignId('research_session_id')->constrained('research_sessions', 'research_session_id')->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained('education_topics', 'topic_id')->cascadeOnDelete();

            $table->integer('interaction_count')->default(0);
            $table->decimal('quiz_score', 5, 2)->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['research_session_id', 'topic_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('education_exposures');
    }
};
