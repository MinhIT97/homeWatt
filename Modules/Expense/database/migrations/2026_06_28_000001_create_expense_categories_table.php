<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_id')->constrained('homes')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('type', 20); // income | expense
            $table->string('icon', 50)->nullable();
            $table->string('color', 7)->nullable();
            $table->boolean('is_system')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['home_id', 'type'], 'expense_categories_home_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
