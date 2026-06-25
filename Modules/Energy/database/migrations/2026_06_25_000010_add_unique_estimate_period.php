<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('energy_estimates', function (Blueprint $table) {
            $table->unique(
                ['device_id', 'period_type', 'period_start'],
                'energy_estimates_device_period_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('energy_estimates', function (Blueprint $table) {
            $table->dropUnique('energy_estimates_device_period_unique');
        });
    }
};
