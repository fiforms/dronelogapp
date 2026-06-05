<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flight_risk_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_id')->constrained()->cascadeOnDelete();
            $table->foreignId('risk_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label', 255);         // snapshot in case item is later edited/removed
            $table->unsignedTinyInteger('score'); // 0–3
            $table->text('mitigation_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_risk_scores');
    }
};
