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
        Schema::table('flights', function (Blueprint $table) {
            $table->unsignedTinyInteger('battery_pct_start')->nullable()->after('battery_id');
            $table->unsignedTinyInteger('battery_pct_end')->nullable()->after('battery_pct_start');
            $table->unsignedSmallInteger('duration_minutes')->nullable()->after('ended_at');
        });
    }

    public function down(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->dropColumn(['battery_pct_start', 'battery_pct_end', 'duration_minutes']);
        });
    }
};
