<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_id')->constrained('homes')->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained('wallets')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('type', 20); // income | expense
            $table->decimal('amount', 16, 2);
            $table->string('currency', 3)->default('VND');
            $table->string('description', 255)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('occurred_at');
            $table->string('reference', 100)->nullable();
            $table->foreignId('transfer_id')->nullable()->constrained('transfers')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['home_id', 'occurred_at'], 'expenses_home_occurred_idx');
            $table->index(['wallet_id', 'occurred_at'], 'expenses_wallet_occurred_idx');
            $table->index('category_id', 'expenses_category_idx');
            $table->index(['home_id', 'type', 'occurred_at'], 'expenses_home_type_occurred_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
