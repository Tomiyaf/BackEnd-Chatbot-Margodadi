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
        Schema::create('public_services', function (Blueprint $table) {
            $table->id('service_id');
            $table->foreignId('category_id')->nullable()->constrained('service_categories', 'category_id')->nullOnDelete();
            $table->string('title', 200);
            $table->string('slug', 200)->unique();
            $table->string('category_badge', 50)->nullable();
            $table->string('sub_category_badge', 100)->nullable();
            $table->text('description')->nullable();
            $table->text('legal_basis')->nullable();
            $table->jsonb('requirements')->nullable();
            $table->jsonb('steps')->nullable();
            $table->string('sla_duration', 100)->nullable();
            $table->string('cost_info', 100)->nullable();
            $table->string('officer_in_charge', 150)->nullable();
            $table->text('download_url')->nullable();
            $table->jsonb('tags')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_services');
    }
};
