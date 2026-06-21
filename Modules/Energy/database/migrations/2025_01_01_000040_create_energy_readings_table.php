<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('energy_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->timestamp('recorded_at');
            $table->decimal('watts', 10, 2)->nullable();
            $table->decimal('kwh', 12, 4)->nullable();
            $table->string('source')->default('manual');
            $table->string('measurement_type')->default('instant');
            $table->integer('interval_minutes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_readings');
    }
};
