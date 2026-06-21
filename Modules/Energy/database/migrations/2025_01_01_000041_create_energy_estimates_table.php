<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('energy_estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->string('period_type')->default('monthly');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('method')->default('continuous');
            $table->decimal('estimated_kwh', 12, 4);
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->decimal('confidence', 5, 2)->default(0.5);
            $table->decimal('lower_range_kwh', 12, 4)->nullable();
            $table->decimal('upper_range_kwh', 12, 4)->nullable();
            $table->json('input_snapshot')->nullable();
            $table->foreignId('tariff_plan_id')->nullable()->constrained('tariff_plans')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_estimates');
    }
};
