<?php

namespace Modules\Device\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Device\Models\Device;

class DevicesImport implements ToModel, WithHeadingRow
{
    public function __construct(private int $roomId) {}

    public function model(array $row): Device
    {
        return new Device([
            'room_id' => $this->roomId,
            'name' => $row['ten_thiet_bi'] ?? $row['name'] ?? 'Unknown',
            'brand' => $row['thuong_hieu'] ?? $row['brand'] ?? null,
            'model' => $row['model'] ?? $row['model'] ?? null,
            'serial' => $row['serial'] ?? $row['serial'] ?? null,
            'status' => 'active',
        ]);
    }
}
