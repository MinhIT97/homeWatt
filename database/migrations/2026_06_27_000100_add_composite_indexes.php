<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Composite index for expense queries: home_id + type + occurred_at + transfer_id (M6)
        Schema::table('expenses', function (Blueprint $table) {
            $table->index(
                ['home_id', 'type', 'occurred_at', 'transfer_id'],
                'expenses_home_type_occurred_transfer_idx'
            );
        });

        // Audit log indexes for filtering and pagination (M4)
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('action', 'audit_logs_action_idx');
            $table->index('user_id', 'audit_logs_user_id_idx');
            $table->index('created_at', 'audit_logs_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('expenses_home_type_occurred_transfer_idx');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_action_idx');
            $table->dropIndex('audit_logs_user_id_idx');
            $table->dropIndex('audit_logs_created_at_idx');
        });
    }
};
