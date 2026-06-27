<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_id')->constrained('homes')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('type', 20); // cash | bank
            $table->string('currency', 3)->default('VND');
            $table->decimal('balance', 16, 2)->default(0);
            $table->decimal('opening_balance', 16, 2)->default(0);
            $table->string('icon', 50)->nullable();
            $table->string('color', 7)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['home_id', 'is_archived'], 'wallets_home_archived_idx');
            $table->index(['home_id', 'sort_order'], 'wallets_home_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
