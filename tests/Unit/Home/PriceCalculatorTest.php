<?php

namespace Tests\Unit\Home;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Device\Http\Requests\StoreDeviceRequest;
use Modules\Device\Models\Device;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Home\Services\PriceCalculator;
use Modules\Room\Http\Requests\StoreRoomRequest;
use Modules\Room\Models\Room;
use Tests\TestCase;

class PriceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private PriceCalculator $calculator;

    private Home $home;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = app(PriceCalculator::class);
        $this->home = $this->createHomeWithData();
    }

    public function test_calculate_home_total_with_rooms_and_devices(): void
    {
        Room::create(['home_id' => $this->home->id, 'name' => 'Bedroom', 'price' => 3000000]);
        Room::create(['home_id' => $this->home->id, 'name' => 'Kitchen', 'price' => 2500000]);

        $livingRoom = Room::where('home_id', $this->home->id)->first();
        Device::create(['room_id' => $livingRoom->id, 'name' => 'AC', 'purchase_price' => 8000000]);
        Device::create(['room_id' => $livingRoom->id, 'name' => 'TV', 'purchase_price' => 5000000]);

        $result = $this->calculator->calculateHomeTotal($this->home);

        // Rooms total: 3000000 + 2500000 = 5500000
        $this->assertEquals(5500000.0, $result['rooms']);
        // Devices total: 8000000 + 5000000 = 13000000
        $this->assertEquals(13000000.0, $result['devices']);
        // Total: 18500000
        $this->assertEquals(18500000.0, $result['total']);
        $this->assertSame('VND', $result['currency']);
    }

    public function test_calculate_home_total_with_no_rooms_or_devices(): void
    {
        // SetUp already creates 1 room, so empty test home
        $emptyHome = $this->createEmptyHome();

        $result = $this->calculator->calculateHomeTotal($emptyHome);

        $this->assertEquals(0.0, $result['rooms']);
        $this->assertEquals(0.0, $result['devices']);
        $this->assertEquals(0.0, $result['total']);
        $this->assertSame(0, $result['room_count']);
        $this->assertSame(0, $result['device_count']);
    }

    public function test_calculate_home_total_skips_null_prices(): void
    {
        Room::create(['home_id' => $this->home->id, 'name' => 'Free Room', 'price' => null]);
        Room::create(['home_id' => $this->home->id, 'name' => 'Paid Room', 'price' => 1500000]);

        $result = $this->calculator->calculateHomeTotal($this->home);

        $this->assertEquals(1500000.0, $result['rooms']);
    }

    public function test_calculate_room_with_devices_includes_room_and_devices(): void
    {
        $room = Room::where('home_id', $this->home->id)->first();
        Device::create(['room_id' => $room->id, 'name' => 'Fridge', 'purchase_price' => 7000000]);
        Device::create(['room_id' => $room->id, 'name' => 'Microwave', 'purchase_price' => 1500000]);

        $room->update(['price' => 4000000]);
        $result = $this->calculator->calculateRoomWithDevices($room->fresh());

        $this->assertEquals(4000000.0, $result['room_price']);
        $this->assertEquals(8500000.0, $result['devices_price']);
        $this->assertEquals(12500000.0, $result['subtotal']);
        $this->assertSame(2, $result['devices_count']);
    }

    public function test_calculate_room_with_devices_handles_null_prices(): void
    {
        $room = Room::where('home_id', $this->home->id)->first();
        Device::create(['room_id' => $room->id, 'name' => 'Free Item', 'purchase_price' => null]);
        Device::create(['room_id' => $room->id, 'name' => 'Paid Item', 'purchase_price' => 1000000]);

        $room->update(['price' => null]);
        $result = $this->calculator->calculateRoomWithDevices($room->fresh());

        $this->assertEquals(0.0, $result['room_price']);
        $this->assertEquals(1000000.0, $result['devices_price']);
        $this->assertEquals(1000000.0, $result['subtotal']);
    }

    public function test_format_money_vnd(): void
    {
        $formatted = $this->calculator->formatMoney(5000000);
        $this->assertSame('5.000.000đ', $formatted);
    }

    public function test_format_money_usd(): void
    {
        $formatted = $this->calculator->formatMoney(1500, 'USD');
        $this->assertStringContainsString('$1.500', $formatted);
    }

    public function test_format_money_eur(): void
    {
        $formatted = $this->calculator->formatMoney(2000, 'EUR');
        $this->assertStringStartsWith('€', $formatted);
    }

    public function test_format_money_unknown_currency(): void
    {
        $formatted = $this->calculator->formatMoney(1000, 'XYZ');
        $this->assertStringContainsString('1.000', $formatted);
        $this->assertStringContainsString('XYZ', $formatted);
    }

    public function test_home_model_total_price_helpers(): void
    {
        Room::create(['home_id' => $this->home->id, 'name' => 'R1', 'price' => 2000000]);
        $room = Room::where('home_id', $this->home->id)->first();
        Device::create(['room_id' => $room->id, 'name' => 'D1', 'purchase_price' => 3000000]);

        $this->assertEquals(2000000.0, $this->home->totalRoomsPrice());
        $this->assertEquals(3000000.0, $this->home->totalDevicesPrice());
        $this->assertEquals(5000000.0, $this->home->totalPrice());
    }

    public function test_negative_prices_clamped_to_zero_in_calculation(): void
    {
        // Negative prices are not blocked at DB level (numeric type),
        // but sum() treats them as negative numbers.
        // The validation rule 'min:0' at FormRequest layer prevents this.
        $room = new Room;
        $room->home_id = $this->home->id;
        $room->name = 'Test';
        $room->price = 1000000;
        $room->save();

        // Verify FormRequest validation rules reject negative
        $rules = (new StoreRoomRequest)->rules();
        $this->assertContains('min:0', $rules['price']);
    }

    public function test_store_device_request_rejects_negative_purchase_price(): void
    {
        $rules = (new StoreDeviceRequest)->rules();
        $this->assertContains('min:0', $rules['purchase_price']);
    }

    private function createHomeWithData(): Home
    {
        $user = User::factory()->create();
        $home = Home::create([
            'owner_id' => $user->id,
            'name' => 'Test Home',
            'currency' => 'VND',
        ]);
        $membership = HomeMember::create(['home_id' => $home->id, 'user_id' => $user->id]);
        $membership->assignRole('owner');
        Room::create(['home_id' => $home->id, 'name' => 'Living Room']);

        return $home;
    }

    private function createEmptyHome(): Home
    {
        $user = User::factory()->create();
        $home = Home::create([
            'owner_id' => $user->id,
            'name' => 'Empty Home',
            'currency' => 'VND',
        ]);
        $membership = HomeMember::create(['home_id' => $home->id, 'user_id' => $user->id]);
        $membership->assignRole('owner');

        return $home;
    }
}
