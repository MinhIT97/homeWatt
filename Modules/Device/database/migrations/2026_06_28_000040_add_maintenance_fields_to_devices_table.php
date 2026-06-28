<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->unsignedInteger('maintenance_interval')->nullable()->after('warranty_unit'); // interval in months
            $table->date('last_maintained_at')->nullable()->after('maintenance_interval');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['maintenance_interval', 'last_maintained_at']);
        });
    }
};
