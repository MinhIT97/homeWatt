<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_usage_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->decimal('hours_per_day', 5, 2)->nullable()->comment('Average hours used per day');
            $table->integer('days_per_week')->nullable()->default(7);
            $table->decimal('duty_cycle', 5, 2)->nullable()->comment('Duty cycle 0.00-1.00');
            $table->string('season')->nullable()->comment('all, summer, winter');
            $table->string('source')->default('manual')->comment('manual, ai, measured');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_usage_profiles');
    }
};
