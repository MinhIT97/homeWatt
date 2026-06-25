<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // energy_readings: composite index for the most common query pattern
        Schema::table('energy_readings', function (Blueprint $table) {
            $table->index(['device_id', 'recorded_at'], 'energy_readings_device_recorded_idx');
            $table->index(['recorded_at'], 'energy_readings_recorded_idx');
        });

        // ai_analysis_requests: queries by user, media, status
        Schema::table('ai_analysis_requests', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'ai_analysis_user_status_idx');
            $table->index(['media_id'], 'ai_analysis_media_idx');
        });

        // monthly_energy_summaries: queries by home + year + month
        Schema::table('monthly_energy_summaries', function (Blueprint $table) {
            $table->index(['home_id', 'year', 'month'], 'monthly_summaries_home_year_month_idx');
        });

        // home_members: queries by user_id (already has unique home_id+user_id)
        Schema::table('home_members', function (Blueprint $table) {
            $table->index('user_id', 'home_members_user_idx');
        });

        // devices: queries by room_id (FK already has index typically)
        // rooms: queries by home_id (FK already has index typically)

        // tariff_plans: queries by effective_from, region/type
        Schema::table('tariff_plans', function (Blueprint $table) {
            $table->index(['region', 'type', 'status'], 'tariff_plans_region_type_status_idx');
            $table->index('effective_from', 'tariff_plans_effective_from_idx');
        });

        // tariff_tiers: queries by plan_id
        Schema::table('tariff_tiers', function (Blueprint $table) {
            $table->index(['tariff_plan_id', 'tier_number'], 'tariff_tiers_plan_tier_idx');
        });

        // homes: queries by owner_id
        // (FK index likely exists, but ensure)
        Schema::table('homes', function (Blueprint $table) {
            $table->index('owner_id', 'homes_owner_idx');
        });
    }

    public function down(): void
    {
        Schema::table('energy_readings', function (Blueprint $table) {
            $table->dropIndex('energy_readings_device_recorded_idx');
            $table->dropIndex('energy_readings_recorded_idx');
        });

        Schema::table('ai_analysis_requests', function (Blueprint $table) {
            $table->dropIndex('ai_analysis_user_status_idx');
            $table->dropIndex('ai_analysis_media_idx');
        });

        Schema::table('monthly_energy_summaries', function (Blueprint $table) {
            $table->dropIndex('monthly_summaries_home_year_month_idx');
        });

        Schema::table('home_members', function (Blueprint $table) {
            $table->dropIndex('home_members_user_idx');
        });

        Schema::table('tariff_plans', function (Blueprint $table) {
            $table->dropIndex('tariff_plans_region_type_status_idx');
            $table->dropIndex('tariff_plans_effective_from_idx');
        });

        Schema::table('tariff_tiers', function (Blueprint $table) {
            $table->dropIndex('tariff_tiers_plan_tier_idx');
        });

        Schema::table('homes', function (Blueprint $table) {
            $table->dropIndex('homes_owner_idx');
        });
    }
};
