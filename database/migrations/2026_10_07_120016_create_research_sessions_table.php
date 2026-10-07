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
        Schema::create('research_sessions', function (Blueprint $table) {
            $table->id('research_session_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('conversations', 'conversation_id')->nullOnDelete();

            $table->string('anonymous_code', 50)->index();
            $table->string('education_version', 50)->nullable();
            $table->decimal('quiz_score', 5, 2)->nullable();
            $table->string('status', 50)->default('STARTED')->index();

            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'conversation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('research_sessions');
    }
};
