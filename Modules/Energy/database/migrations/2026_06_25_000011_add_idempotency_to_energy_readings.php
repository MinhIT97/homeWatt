<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('energy_readings', function (Blueprint $table) {
            $table->string('idempotency_key', 100)->nullable()->after('measurement_type');
            $table->unique(
                ['device_id', 'idempotency_key'],
                'energy_readings_device_idempotency_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('energy_readings', function (Blueprint $table) {
            $table->dropUnique('energy_readings_device_idempotency_unique');
            $table->dropColumn('idempotency_key');
        });
    }
};
