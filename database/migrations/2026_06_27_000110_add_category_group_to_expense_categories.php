<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->string('category_group', 50)->nullable()->after('type');
            $table->index('category_group', 'expense_categories_group_idx');
        });
    }

    public function down(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropIndex('expense_categories_group_idx');
            $table->dropColumn('category_group');
        });
    }
};
