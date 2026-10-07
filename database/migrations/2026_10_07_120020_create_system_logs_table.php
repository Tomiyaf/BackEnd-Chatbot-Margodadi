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
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->foreignId('conversation_id')->nullable()->constrained('conversations', 'conversation_id')->nullOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('messages', 'message_id')->nullOnDelete();

            $table->string('service', 100)->nullable()->index();
            $table->string('event_type', 100)->nullable()->index();

            $table->string('status', 50)->nullable();
            $table->string('error_code', 100)->nullable();

            $table->integer('latency_ms')->nullable();
            $table->jsonb('metadata')->nullable();

            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['conversation_id', 'message_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
