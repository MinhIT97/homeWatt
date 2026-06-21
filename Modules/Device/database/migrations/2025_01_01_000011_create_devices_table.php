<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('device_type_id')->nullable()->constrained('device_types')->nullOnDelete();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial')->nullable();
            $table->string('status')->default('active');
            $table->date('purchased_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
