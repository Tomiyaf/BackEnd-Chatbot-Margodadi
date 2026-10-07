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
        Schema::create('umkms', function (Blueprint $table) {
            $table->id('umkm_id');
            $table->foreignId('category_id')->nullable()->constrained('service_categories', 'category_id')->nullOnDelete();

            $table->string('reg_number', 50)->unique()->nullable();
            $table->string('name', 150)->index();
            $table->string('sub_title', 255)->nullable();
            $table->string('owner_name', 150);
            $table->string('phone', 50)->nullable();
            $table->string('wa_number', 50)->nullable();

            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->text('history')->nullable();

            $table->text('banner_image_url')->nullable();
            $table->jsonb('gallery_urls')->nullable();

            $table->string('legal_certification', 150)->nullable();
            $table->string('legal_number', 150)->nullable();
            $table->string('production_capacity', 100)->nullable();
            $table->string('capacity_note', 150)->nullable();
            $table->string('group_name', 150)->nullable();
            $table->string('group_location', 150)->nullable();

            $table->string('map_title', 150)->nullable();
            $table->text('map_address')->nullable();
            $table->text('map_url')->nullable();

            $table->timestamp('last_verified_at')->nullable();
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('umkms');
    }
};
