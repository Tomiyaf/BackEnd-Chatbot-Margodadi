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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id('conversation_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('service_categories', 'category_id')->nullOnDelete();
            $table->foreignId('assigned_operator_id')->nullable()->constrained('operators', 'operator_id')->nullOnDelete();

            $table->string('channel', 50)->index();
            $table->string('status', 50)->default('OPEN')->index();
            $table->string('priority', 50)->default('MEDIUM')->index();
            $table->boolean('needs_human')->default(false)->index();

            $table->string('citizen_name', 150)->nullable();
            $table->string('external_conversation_id', 150)->nullable();

            $table->timestamp('started_at')->useCurrent()->index();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
