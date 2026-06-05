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
        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('drone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('battery_id')->nullable()->constrained()->nullOnDelete();
            $table->char('client_uuid', 36)->unique(); // UUID generated client-side for dedup
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('location_description', 255)->nullable();
            $table->text('flight_plan')->nullable();
            $table->string('purpose', 20)->default('recreational'); // recreational | commercial
            $table->text('purpose_notes')->nullable();
            $table->string('laanc_status', 20)->default('na'); // received | not_needed | na
            $table->string('laanc_authorization_number', 50)->nullable();
            $table->text('post_flight_notes')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flights');
    }
};
