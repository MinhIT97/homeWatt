<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('energy_bills', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('amount');
            $table->timestamp('reminder_sent_at')->nullable()->after('due_date');
            $table->boolean('is_paid')->default(false)->after('reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('energy_bills', function (Blueprint $table) {
            $table->dropColumn(['due_date', 'reminder_sent_at', 'is_paid']);
        });
    }
};
