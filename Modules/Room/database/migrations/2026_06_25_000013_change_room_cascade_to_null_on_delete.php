<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $foreignKeys = collect(Schema::getForeignKeys('rooms'))->pluck('name');
            if ($foreignKeys->contains('rooms_home_id_foreign')) {
                $table->dropForeign(['home_id']);
            }
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('home_id')->nullable()->change();

            $table->foreign('home_id')
                ->references('id')->on('homes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $foreignKeys = collect(Schema::getForeignKeys('rooms'))->pluck('name');
            if ($foreignKeys->contains('rooms_home_id_foreign')) {
                $table->dropForeign(['home_id']);
            }
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('home_id')->nullable(false)->change();

            $table->foreign('home_id')
                ->references('id')->on('homes')
                ->cascadeOnDelete();
        });
    }
};
