<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->string('disk')->default('private');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('checksum', 64)->nullable();
            $table->string('status')->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
