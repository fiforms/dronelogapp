<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drones', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('notes');
        });

        Schema::table('batteries', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('notes');
        });

        Schema::table('accessories', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('drones', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('batteries', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('accessories', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
