<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('template_code', 50);
            $table->string('channel', 20);
            $table->string('title', 255)->nullable();
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();
            $table->index(['user_id', 'read_at'], 'notif_user_read_idx');
            $table->index('sent_at', 'notif_sent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
