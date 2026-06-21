<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_energy_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_id')->constrained('homes')->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->integer('year');
            $table->integer('month');
            $table->decimal('total_kwh', 12, 4)->default(0);
            $table->decimal('estimated_cost', 12, 2)->default(0);
            $table->integer('reading_count')->default(0);
            $table->integer('estimate_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['home_id', 'room_id', 'device_id', 'year', 'month'], 'monthly_summary_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_energy_summaries');
    }
};
