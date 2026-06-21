<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->decimal('voltage', 10, 2)->nullable()->comment('Voltage in V');
            $table->decimal('current', 10, 2)->nullable()->comment('Current in A');
            $table->decimal('rated_power', 10, 2)->nullable()->comment('Rated power in W');
            $table->decimal('max_power', 10, 2)->nullable()->comment('Maximum power in W');
            $table->decimal('standby_power', 10, 2)->nullable()->comment('Standby power in W');
            $table->decimal('capacity', 10, 2)->nullable()->comment('Capacity (liters, kg, BTU, etc.)');
            $table->json('metadata')->nullable()->comment('Extended metadata');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_specifications');
    }
};
