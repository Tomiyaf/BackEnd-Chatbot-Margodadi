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
        Schema::create('umkm_products', function (Blueprint $table) {
            $table->id('product_id');
            $table->foreignId('umkm_id')->constrained('umkms', 'umkm_id')->cascadeOnDelete();

            $table->string('name', 150)->index();
            $table->text('description')->nullable();
            $table->string('price', 100)->nullable();
            $table->text('image_url')->nullable();
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('umkm_products');
    }
};
