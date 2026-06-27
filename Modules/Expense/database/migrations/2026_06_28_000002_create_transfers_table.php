<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_id')->constrained('homes')->cascadeOnDelete();
            $table->foreignId('from_wallet_id')->constrained('wallets')->restrictOnDelete();
            $table->foreignId('to_wallet_id')->constrained('wallets')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 16, 2);
            $table->decimal('fee', 16, 2)->default(0);
            $table->string('currency', 3)->default('VND');
            $table->text('description')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['home_id', 'occurred_at'], 'transfers_home_occurred_idx');
            $table->index('from_wallet_id', 'transfers_from_wallet_idx');
            $table->index('to_wallet_id', 'transfers_to_wallet_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
