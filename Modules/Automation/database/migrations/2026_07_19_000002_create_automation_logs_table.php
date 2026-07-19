<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('automation_rules')->cascadeOnDelete();
            $table->foreignId('home_id')->constrained()->cascadeOnDelete();
            $table->string('trigger_event', 50);
            $table->json('matched_conditions')->nullable();
            $table->json('executed_actions')->nullable();
            $table->string('status', 20)->default('success'); // success, failed, skipped
            $table->text('error_message')->nullable();
            $table->timestamp('executed_at')->useCurrent();
            $table->timestamps();

            $table->index(['rule_id', 'executed_at'], 'auto_log_rule_time_idx');
            $table->index(['home_id', 'status'], 'auto_log_home_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_logs');
    }
};
