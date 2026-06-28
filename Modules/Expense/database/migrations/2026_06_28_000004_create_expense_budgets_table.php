<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_id')->constrained('homes')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('expense_categories')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('month', 7); // e.g. '2026-06'
            $table->timestamps();

            // Unique combination of home, category, and month
            $table->unique(['home_id', 'category_id', 'month'], 'budget_unique_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_budgets');
    }
};
