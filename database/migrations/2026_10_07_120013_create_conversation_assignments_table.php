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
        Schema::create('conversation_assignments', function (Blueprint $table) {
            $table->id('assignment_id');
            $table->foreignId('conversation_id')->constrained('conversations', 'conversation_id')->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained('operators', 'operator_id')->cascadeOnDelete();

            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('unassigned_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['conversation_id', 'operator_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_assignments');
    }
};
