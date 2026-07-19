<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('description', 500)->nullable();
            $table->string('trigger_event', 50);
            $table->boolean('is_active')->default(true);
            $table->json('conditions'); // [{field, operator, value}]
            $table->json('actions');    // [{type, config}]
            $table->integer('priority')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('trigger_count')->default(0);
            $table->timestamps();

            $table->index(['home_id', 'trigger_event', 'is_active'], 'auto_rules_active_idx');
            $table->index('priority', 'auto_rules_priority_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_rules');
    }
};
