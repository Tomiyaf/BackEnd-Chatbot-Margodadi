<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->string('username', 100)->nullable()->unique()->after('name');
        });

        // Backfill username for existing operators based on email prefix
        $operators = DB::table('operators')->whereNull('username')->get();
        foreach ($operators as $operator) {
            $emailParts = explode('@', $operator->email);
            $baseUsername = Str::slug($emailParts[0] ?? 'operator', '.');
            if (empty($baseUsername)) {
                $baseUsername = 'operator.'.$operator->operator_id;
            }

            $username = $baseUsername;
            $counter = 1;
            while (DB::table('operators')->where('username', $username)->where('operator_id', '!=', $operator->operator_id)->exists()) {
                $username = $baseUsername.$counter;
                $counter++;
            }

            DB::table('operators')
                ->where('operator_id', $operator->operator_id)
                ->update(['username' => $username]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
