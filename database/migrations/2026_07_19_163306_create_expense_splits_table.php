<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_id')->constrained()->cascadeOnDelete();
            $table->foreignId('paid_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('owed_by')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('status', 20)->default('pending'); // pending, partial, settled
            $table->timestamp('settled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['home_id', 'owed_by', 'status'], 'split_user_status_idx');
            $table->index(['home_id', 'paid_by', 'status'], 'split_payer_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_splits');
    }
};
