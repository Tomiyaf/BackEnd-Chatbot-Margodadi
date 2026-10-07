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
        Schema::create('feedback', function (Blueprint $table) {
            $table->id('feedback_id');
            $table->foreignId('conversation_id')->constrained('conversations', 'conversation_id')->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('messages', 'message_id')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();

            $table->integer('rating')->nullable();
            $table->text('comment')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['conversation_id', 'message_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
