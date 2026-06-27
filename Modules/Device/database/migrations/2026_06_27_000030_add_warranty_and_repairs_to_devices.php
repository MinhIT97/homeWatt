<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->unsignedInteger('warranty_duration')->nullable()->after('serial');
            $table->string('warranty_unit')->default('month')->after('warranty_duration'); // 'month' or 'year'
        });

        Schema::create('device_repairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->date('repaired_at');
            $table->decimal('cost', 15, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->string('repairer')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_repairs');

        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['warranty_duration', 'warranty_unit']);
        });
    }
};
