<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_extractions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_analysis_result_id')->constrained('ai_analysis_results')->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('field');
            $table->text('ai_value')->nullable();
            $table->text('confirmed_value')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_extractions');
    }
};
