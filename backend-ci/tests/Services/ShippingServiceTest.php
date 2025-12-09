<?php

namespace Tests\Services;

use App\Services\Shipping\ShippingService;
use App\Repositories\Shipping\ShippingRepository;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;
use InvalidArgumentException;

class ShippingServiceTest extends CIUnitTestCase
{
    private ShippingService $service;
    private ShippingFakeRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new ShippingFakeRepo();
        $this->service = new ShippingService($this->repo);
    }

    public function testCalculateFeeReturnsZeroWhenNoZone(): void
    {
        $result = $this->service->calculateFee(['province_id' => 999]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['data']['shipping_fee']);
        $this->assertNull($result['data']['zone']);
        $this->assertFalse($result['data']['free_shipping']);
    }

    public function testCalculateFeeReturnsZeroWhenNoRate(): void
    {
        $result = $this->service->calculateFee(['province_id' => 3]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['data']['shipping_fee']);
        $this->assertEquals('Zone C', $result['data']['zone']);
    }

    public function testCalculateFeeWithBaseFee(): void
    {
        $result = $this->service->calculateFee(['province_id' => 1, 'weight' => 0]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(30000, $result['data']['shipping_fee']);
        $this->assertEquals('Zone A', $result['data']['zone']);
    }

    public function testCalculateFeeWithWeight(): void
    {
        $result = $this->service->calculateFee(['province_id' => 1, 'weight' => 2]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(50000, $result['data']['shipping_fee']);
    }

    public function testCalculateFeeWithFreeShipping(): void
    {
        $result = $this->service->calculateFee([
            'province_id' => 1,
            'weight' => 2,
            'order_value' => 500000,
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['data']['shipping_fee']);
        $this->assertTrue($result['data']['free_shipping']);
    }

    public function testCalculateFeeWithoutFreeShipping(): void
    {
        $result = $this->service->calculateFee([
            'province_id' => 1,
            'weight' => 2,
            'order_value' => 100000,
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(50000, $result['data']['shipping_fee']);
        $this->assertFalse($result['data']['free_shipping']);
    }

    public function testListZonesReturnsData(): void
    {
        $result = $this->service->listZones();
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        $this->assertCount(3, $result['data']);
    }

    public function testListZonesTransformsData(): void
    {
        $result = $this->service->listZones();
        
        $zone = $result['data'][0];
        $this->assertArrayHasKey('id', $zone);
        $this->assertArrayHasKey('name', $zone);
        $this->assertArrayHasKey('province_ids', $zone);
        $this->assertArrayHasKey('district_ids', $zone);
        $this->assertArrayHasKey('is_active', $zone);
        $this->assertIsArray($zone['province_ids']);
        $this->assertIsBool($zone['is_active']);
    }

    public function testCreateZoneSuccess(): void
    {
        $result = $this->service->createZone(['name' => 'Zone D']);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Zone created', $result['message']);
    }

    public function testCreateZoneThrowsWhenNameEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Zone name is required');
        
        $this->service->createZone(['name' => '']);
    }

    public function testUpdateZoneSuccess(): void
    {
        $result = $this->service->updateZone(1, ['name' => 'Updated Zone']);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Zone updated', $result['message']);
    }

    public function testUpdateZoneThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Zone not found');
        
        $this->service->updateZone(999, ['name' => 'Test']);
    }

    public function testDeleteZoneSuccess(): void
    {
        $result = $this->service->deleteZone(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Zone deleted', $result['message']);
    }

    public function testDeleteZoneThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Zone not found');
        
        $this->service->deleteZone(999);
    }

    public function testGetRatesReturnsData(): void
    {
        $result = $this->service->getRates(1);
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
    }

    public function testCreateRateSuccess(): void
    {
        $result = $this->service->createRate(['zone_id' => 1, 'base_fee' => 50000]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Rate created', $result['message']);
    }

    public function testCreateRateThrowsWhenZoneIdMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Zone ID is required');
        
        $this->service->createRate(['base_fee' => 50000]);
    }

    public function testUpdateRateSuccess(): void
    {
        $result = $this->service->updateRate(1, ['base_fee' => 40000]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Rate updated', $result['message']);
    }

    public function testDeleteRateSuccess(): void
    {
        $result = $this->service->deleteRate(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Rate deleted', $result['message']);
    }

    public function testUpdateZoneWithProvinceIds(): void
    {
        $result = $this->service->updateZone(1, ['province_ids' => [1, 2, 3]]);
        
        $this->assertTrue($result['success']);
    }

    public function testUpdateZoneWithDistrictIds(): void
    {
        $result = $this->service->updateZone(1, ['district_ids' => [10, 20]]);
        
        $this->assertTrue($result['success']);
    }
}

class ShippingFakeRepo extends ShippingRepository
{
    private array $zones = [
        1 => ['id' => 1, 'name' => 'Zone A', 'province_ids' => '[1]', 'district_ids' => '[]', 'is_active' => 1, 'sort_order' => 1],
        2 => ['id' => 2, 'name' => 'Zone B', 'province_ids' => '[2]', 'district_ids' => '[]', 'is_active' => 1, 'sort_order' => 2],
        3 => ['id' => 3, 'name' => 'Zone C', 'province_ids' => '[3]', 'district_ids' => '[]', 'is_active' => 1, 'sort_order' => 3],
    ];

    private array $rates = [
        1 => ['id' => 1, 'zone_id' => 1, 'base_fee' => 30000, 'per_kg_fee' => 10000, 'free_shipping_threshold' => 500000, 'min_weight' => 0, 'max_weight' => 100, 'min_value' => 0, 'max_value' => 999999999],
        2 => ['id' => 2, 'zone_id' => 2, 'base_fee' => 40000, 'per_kg_fee' => 15000, 'free_shipping_threshold' => null, 'min_weight' => 0, 'max_weight' => 100, 'min_value' => 0, 'max_value' => 999999999],
    ];

    public function __construct() {}

    public function findZoneByLocation(?int $provinceId, ?int $districtId): ?array
    {
        foreach ($this->zones as $zone) {
            $provinceIds = json_decode($zone['province_ids'], true) ?? [];
            if (in_array($provinceId, $provinceIds)) {
                return $zone;
            }
        }
        return null;
    }

    public function findApplicableRate(int $zoneId, float $weight, float $orderValue, ?int $partnerId = null): ?array
    {
        foreach ($this->rates as $rate) {
            if ($rate['zone_id'] === $zoneId
                && $weight >= $rate['min_weight']
                && $weight <= $rate['max_weight']
                && $orderValue >= $rate['min_value']
                && $orderValue <= $rate['max_value']) {
                return $rate;
            }
        }
        return null;
    }

    public function findAllZones(array $filters = []): array
    {
        return array_values($this->zones);
    }

    public function findZoneById(int $id): ?array
    {
        return $this->zones[$id] ?? null;
    }

    public function createZone(array $data): array
    {
        $id = max(array_keys($this->zones)) + 1;
        $this->zones[$id] = array_merge($data, ['id' => $id]);
        return $this->zones[$id];
    }

    public function updateZone(int $id, array $data): array
    {
        if (isset($this->zones[$id])) {
            $this->zones[$id] = array_merge($this->zones[$id], $data);
        }
        return $this->zones[$id] ?? [];
    }

    public function deleteZone(int $id): bool
    {
        unset($this->zones[$id]);
        return true;
    }

    public function findRatesByZone(int $zoneId): array
    {
        return array_values(array_filter($this->rates, fn($r) => $r['zone_id'] === $zoneId));
    }

    public function createRate(array $data): array
    {
        $id = max(array_keys($this->rates)) + 1;
        $this->rates[$id] = array_merge($data, ['id' => $id]);
        return $this->rates[$id];
    }

    public function updateRate(int $id, array $data): array
    {
        if (isset($this->rates[$id])) {
            $this->rates[$id] = array_merge($this->rates[$id], $data);
        }
        return $this->rates[$id] ?? [];
    }

    public function deleteRate(int $id): bool
    {
        unset($this->rates[$id]);
        return true;
    }
}
