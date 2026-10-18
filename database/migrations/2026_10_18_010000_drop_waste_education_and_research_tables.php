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
        Schema::dropIfExists('education_exposures');
        Schema::dropIfExists('education_topics');
        Schema::dropIfExists('research_sessions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Legacy tables removed permanently
    }
};
