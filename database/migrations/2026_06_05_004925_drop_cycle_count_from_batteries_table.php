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
        Schema::table('batteries', function (Blueprint $table) {
            $table->dropColumn('cycle_count');
        });
    }

    public function down(): void
    {
        Schema::table('batteries', function (Blueprint $table) {
            $table->unsignedSmallInteger('cycle_count')->default(0)->after('purchase_date');
        });
    }
};
