<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_analysis_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_analysis_request_id')->constrained('ai_analysis_requests')->cascadeOnDelete();
            $table->json('raw_response')->nullable();
            $table->json('normalized_data')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->decimal('cost', 10, 6)->nullable();
            $table->unsignedInteger('processing_time_ms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_analysis_results');
    }
};
