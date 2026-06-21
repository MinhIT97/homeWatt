<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariff_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider')->nullable();
            $table->string('region')->nullable();
            $table->string('type')->default('residential');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariff_plans');
    }
};
