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
        Schema::table('umkms', function (Blueprint $table) {
            $table->foreignId('kb_document_id')->nullable()->constrained('kb_documents', 'document_id')->nullOnDelete();
            $table->unsignedBigInteger('rag_document_id')->nullable();
            $table->timestamp('ai_synced_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('umkms', function (Blueprint $table) {
            $table->dropForeign(['kb_document_id']);
            $table->dropColumn(['kb_document_id', 'rag_document_id', 'ai_synced_at']);
        });
    }
};
