<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Device\Database\Seeders\DeviceTypeSeeder;
use Modules\Tariff\Database\Seeders\VietnamResidentialTariffSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DeviceTypeSeeder::class,
            VietnamResidentialTariffSeeder::class,
        ]);
    }
}
