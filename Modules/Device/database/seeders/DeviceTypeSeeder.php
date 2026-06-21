<?php

namespace Modules\Device\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Device\Models\DeviceType;

class DeviceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Air Conditioner', 'slug' => 'air_conditioner', 'icon' => '❄️', 'default_duty_cycle' => 0.60, 'description' => 'Split, window, or central AC unit'],
            ['name' => 'Refrigerator', 'slug' => 'refrigerator', 'icon' => '🧊', 'default_duty_cycle' => 0.50, 'description' => 'Fridge, freezer, or combo unit'],
            ['name' => 'Washing Machine', 'slug' => 'washing_machine', 'icon' => '👕', 'default_duty_cycle' => 0.15, 'description' => 'Top-load or front-load washer'],
            ['name' => 'Water Heater', 'slug' => 'water_heater', 'icon' => '🔥', 'default_duty_cycle' => 0.30, 'description' => 'Tank or tankless water heater'],
            ['name' => 'Television', 'slug' => 'television', 'icon' => '📺', 'default_duty_cycle' => 1.00, 'description' => 'LED, OLED, or LCD TV'],
            ['name' => 'Fan', 'slug' => 'fan', 'icon' => '🌀', 'default_duty_cycle' => 1.00, 'description' => 'Ceiling, standing, or table fan'],
            ['name' => 'Lighting', 'slug' => 'lighting', 'icon' => '💡', 'default_duty_cycle' => 1.00, 'description' => 'Indoor or outdoor lighting'],
            ['name' => 'Rice Cooker', 'slug' => 'rice_cooker', 'icon' => '🍚', 'default_duty_cycle' => 0.10, 'description' => 'Electric rice cooker'],
            ['name' => 'Microwave', 'slug' => 'microwave', 'icon' => '📡', 'default_duty_cycle' => 0.05, 'description' => 'Microwave oven'],
            ['name' => 'Computer', 'slug' => 'computer', 'icon' => '💻', 'default_duty_cycle' => 1.00, 'description' => 'Desktop or laptop'],
            ['name' => 'Electric Kettle', 'slug' => 'electric_kettle', 'icon' => '🫖', 'default_duty_cycle' => 0.05, 'description' => 'Electric water kettle'],
            ['name' => 'Vacuum Cleaner', 'slug' => 'vacuum_cleaner', 'icon' => '🧹', 'default_duty_cycle' => 0.02, 'description' => 'Upright, canister, or robot vacuum'],
            ['name' => 'Other', 'slug' => 'other', 'icon' => '🔌', 'default_duty_cycle' => null, 'description' => 'Other appliance'],
        ];

        foreach ($types as $type) {
            DeviceType::create($type);
        }
    }
}
