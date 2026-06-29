<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('energy_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_id')->constrained('homes')->cascadeOnDelete();
            $table->foreignId('expense_id')->nullable()->unique()->constrained('expenses')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_code')->nullable();
            $table->string('billing_period', 20)->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->decimal('old_index', 12, 2)->nullable();
            $table->decimal('new_index', 12, 2)->nullable();
            $table->decimal('kwh', 12, 4)->nullable();
            $table->decimal('amount', 16, 2);
            $table->string('currency', 3)->default('VND');
            $table->string('source')->default('telegram_ai');
            $table->json('raw_payload')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['home_id', 'billing_period'], 'energy_bills_home_period_idx');
            $table->index(['home_id', 'period_start'], 'energy_bills_home_period_start_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_bills');
    }
};
