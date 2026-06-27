<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Purchase price (VND by default, configurable per home)
            $table->decimal('purchase_price', 14, 2)->nullable()->after('purchased_at');
            $table->index('purchase_price');
        });

        Schema::table('rooms', function (Blueprint $table) {
            // Room rental/usage cost (per month)
            $table->decimal('price', 14, 2)->nullable()->after('sort_order');
            $table->index('price');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex(['purchase_price']);
            $table->dropColumn('purchase_price');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex(['price']);
            $table->dropColumn('price');
        });
    }
};
