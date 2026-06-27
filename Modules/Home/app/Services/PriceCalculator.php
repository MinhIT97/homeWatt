<?php

namespace Modules\Home\Services;

use Modules\Device\Models\Device;
use Modules\Home\Models\Home;
use Modules\Room\Models\Room;

class PriceCalculator
{
    public const DEFAULT_CURRENCY = 'VND';

    public function calculateHomeTotal(Home $home): array
    {
        $roomsPrice = $this->calculateRoomsPrice($home);
        $devicesPrice = $this->calculateDevicesPrice($home);

        return [
            'rooms' => $roomsPrice,
            'devices' => $devicesPrice,
            'total' => $roomsPrice + $devicesPrice,
            'currency' => $home->currency ?: self::DEFAULT_CURRENCY,
            'room_count' => $home->rooms()->count(),
            'device_count' => $this->countDevices($home),
        ];
    }

    public function calculateRoomsPrice(Home $home): float
    {
        return (float) $home->rooms()->sum('price');
    }

    public function calculateDevicesPrice(Home $home): float
    {
        return (float) Device::whereHas(
            'room',
            fn ($q) => $q->where('home_id', $home->id)
        )->sum('purchase_price');
    }

    public function countDevices(Home $home): int
    {
        return Device::whereHas(
            'room',
            fn ($q) => $q->where('home_id', $home->id)
        )->count();
    }

    public function calculateRoomWithDevices(Room $room): array
    {
        $devices = $room->devices()->withTrashed()->get(['id', 'name', 'purchase_price']);

        return [
            'room_price' => (float) $room->price,
            'devices_price' => (float) $devices->sum('purchase_price'),
            'devices_count' => $devices->count(),
            'subtotal' => (float) $room->price + (float) $devices->sum('purchase_price'),
        ];
    }

    public function formatMoney(float $amount, string $currency = self::DEFAULT_CURRENCY): string
    {
        $formatted = number_format($amount, 0, ',', '.');

        return match ($currency) {
            'VND' => "{$formatted}đ",
            'USD' => "\${$formatted}",
            'EUR' => "€{$formatted}",
            default => "{$formatted} {$currency}",
        };
    }
}
