<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropForeign(['home_id']);
            $table->foreign('home_id')
                ->references('id')->on('homes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropForeign(['home_id']);
            $table->foreign('home_id')
                ->references('id')->on('homes')
                ->cascadeOnDelete();
        });
    }
};
