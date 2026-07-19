<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('template_code', 50);
            $table->json('channels');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'template_code'], 'user_template_pref_unique');
            $table->foreign('template_code', 'user_notif_prefs_template_fk')->references('code')->on('notification_templates')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};
