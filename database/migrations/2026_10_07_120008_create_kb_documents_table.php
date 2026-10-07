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
        Schema::create('kb_documents', function (Blueprint $table) {
            $table->id('document_id');
            $table->string('title', 255);
            $table->string('domain', 50)->index();
            $table->text('source');
            $table->string('validator', 150)->nullable();
            $table->string('version', 50)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kb_documents');
    }
};
