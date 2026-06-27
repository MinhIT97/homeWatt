<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $foreignKeys = collect(Schema::getForeignKeys('devices'))->pluck('name');
            if ($foreignKeys->contains('devices_room_id_foreign')) {
                $table->dropForeign(['room_id']);
            }
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->unsignedBigInteger('room_id')->nullable()->change();

            $table->foreign('room_id')
                ->references('id')->on('rooms')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $foreignKeys = collect(Schema::getForeignKeys('devices'))->pluck('name');
            if ($foreignKeys->contains('devices_room_id_foreign')) {
                $table->dropForeign(['room_id']);
            }
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->unsignedBigInteger('room_id')->nullable(false)->change();

            $table->foreign('room_id')
                ->references('id')->on('rooms')
                ->cascadeOnDelete();
        });
    }
};
