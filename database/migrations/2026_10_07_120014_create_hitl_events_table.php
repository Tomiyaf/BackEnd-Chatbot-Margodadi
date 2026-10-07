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
        Schema::create('hitl_events', function (Blueprint $table) {
            $table->id('event_id');
            $table->foreignId('conversation_id')->constrained('conversations', 'conversation_id')->cascadeOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('operators', 'operator_id')->nullOnDelete();

            $table->string('event_type', 50)->index();
            $table->text('notes')->nullable();

            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['conversation_id', 'operator_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hitl_events');
    }
};
