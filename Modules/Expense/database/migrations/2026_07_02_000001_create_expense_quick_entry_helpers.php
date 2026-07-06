<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_quick_entry_habits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_id')->constrained('homes')->cascadeOnDelete();
            $table->foreignId('wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->string('keyword', 100);
            $table->unsignedInteger('usage_count')->default(1);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'home_id', 'keyword', 'wallet_id', 'category_id'], 'quick_habit_unique_pair');
            $table->index(['user_id', 'home_id', 'keyword', 'usage_count'], 'quick_habit_lookup_index');
        });

        Schema::create('expense_transaction_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('home_id')->constrained('homes')->cascadeOnDelete();
            $table->foreignId('wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->foreignId('from_wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            $table->foreignId('to_wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            $table->string('name');
            $table->string('type', 20)->default('expense');
            $table->decimal('amount', 16, 2)->nullable();
            $table->string('description')->nullable();
            $table->string('notes')->nullable();
            $table->string('icon', 20)->nullable();
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['home_id', 'user_id', 'sort_order'], 'txn_templates_home_user_sort_idx');
        });

        Schema::create('expense_recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_id')->constrained('homes')->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('expense_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 20)->default('expense');
            $table->decimal('amount', 16, 2);
            $table->string('frequency', 20)->default('monthly');
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->date('start_date');
            $table->date('next_due_date');
            $table->timestamp('last_generated_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['home_id', 'is_active', 'next_due_date'], 'recurring_txn_home_active_due_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_recurring_transactions');
        Schema::dropIfExists('expense_transaction_templates');
        Schema::dropIfExists('expense_quick_entry_habits');
    }
};
