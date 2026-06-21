<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariff_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tariff_plan_id')->constrained('tariff_plans')->cascadeOnDelete();
            $table->integer('tier_number');
            $table->decimal('limit_kwh', 12, 2)->nullable();
            $table->decimal('rate', 12, 4);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('surcharge', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariff_tiers');
    }
};
