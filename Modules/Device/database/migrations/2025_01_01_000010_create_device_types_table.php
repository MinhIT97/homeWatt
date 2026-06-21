<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->decimal('default_duty_cycle', 5, 2)->nullable()->comment('Default duty cycle 0.00-1.00');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_types');
    }
};
