<?php

namespace Tests\Services;

use App\Services\Locations\LocationService;
use App\Repositories\Locations\LocationRepository;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

class LocationServiceTest extends CIUnitTestCase
{
    private LocationService $service;
    private LocationFakeRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new LocationFakeRepo();
        $this->service = new LocationService($this->repo);
    }

    public function testListProvincesReturnsData(): void
    {
        $result = $this->service->listProvinces();
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        $this->assertEquals(2, $result['total']);
    }

    public function testListProvincesTransformsData(): void
    {
        $result = $this->service->listProvinces();
        
        $province = $result['data'][0];
        $this->assertArrayHasKey('id', $province);
        $this->assertArrayHasKey('code', $province);
        $this->assertArrayHasKey('name', $province);
        $this->assertArrayHasKey('full_name', $province);
        $this->assertArrayHasKey('name_en', $province);
    }

    public function testListDistrictsReturnsDataWithProvince(): void
    {
        $result = $this->service->listDistricts(1);
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('province', $result);
        $this->assertEquals(1, $result['province']['id']);
    }

    public function testListDistrictsThrowsWhenProvinceNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Province not found');
        
        $this->service->listDistricts(999);
    }

    public function testListDistrictsTransformsData(): void
    {
        $result = $this->service->listDistricts(1);
        
        $district = $result['data'][0];
        $this->assertArrayHasKey('id', $district);
        $this->assertArrayHasKey('province_id', $district);
        $this->assertArrayHasKey('code', $district);
        $this->assertArrayHasKey('name', $district);
    }

    public function testListWardsReturnsDataWithDistrict(): void
    {
        $result = $this->service->listWards(1);
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('district', $result);
        $this->assertEquals(1, $result['district']['id']);
    }

    public function testListWardsThrowsWhenDistrictNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('District not found');
        
        $this->service->listWards(999);
    }

    public function testListWardsTransformsData(): void
    {
        $result = $this->service->listWards(1);
        
        $ward = $result['data'][0];
        $this->assertArrayHasKey('id', $ward);
        $this->assertArrayHasKey('district_id', $ward);
        $this->assertArrayHasKey('code', $ward);
        $this->assertArrayHasKey('name', $ward);
    }

    public function testGetStatsReturnsAllCounts(): void
    {
        $result = $this->service->getStats();
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('provinces', $result['data']);
        $this->assertArrayHasKey('districts', $result['data']);
        $this->assertArrayHasKey('wards', $result['data']);
        $this->assertEquals(2, $result['data']['provinces']);
        $this->assertEquals(3, $result['data']['districts']);
        $this->assertEquals(5, $result['data']['wards']);
    }

    public function testListProvincesWithFilters(): void
    {
        $result = $this->service->listProvinces(['search' => 'Ha Noi']);
        
        $this->assertTrue($result['success']);
    }

    public function testListDistrictsWithFilters(): void
    {
        $result = $this->service->listDistricts(1, ['search' => 'Hoan Kiem']);
        
        $this->assertTrue($result['success']);
    }

    public function testListWardsWithFilters(): void
    {
        $result = $this->service->listWards(1, ['search' => 'Hang']);
        
        $this->assertTrue($result['success']);
    }
}

class LocationFakeRepo extends LocationRepository
{
    private array $provinceData = [
        1 => ['id' => 1, 'code' => '01', 'name' => 'Ha Noi', 'full_name' => 'Thanh pho Ha Noi', 'name_en' => 'Hanoi'],
        2 => ['id' => 2, 'code' => '79', 'name' => 'TP HCM', 'full_name' => 'Thanh pho Ho Chi Minh', 'name_en' => 'Ho Chi Minh City'],
    ];

    private array $districtData = [
        1 => ['id' => 1, 'province_id' => 1, 'code' => '001', 'name' => 'Hoan Kiem', 'full_name' => 'Quan Hoan Kiem', 'name_en' => 'Hoan Kiem District'],
        2 => ['id' => 2, 'province_id' => 1, 'code' => '002', 'name' => 'Ba Dinh', 'full_name' => 'Quan Ba Dinh', 'name_en' => 'Ba Dinh District'],
        3 => ['id' => 3, 'province_id' => 2, 'code' => '760', 'name' => 'Quan 1', 'full_name' => 'Quan 1', 'name_en' => 'District 1'],
    ];

    private array $wardData = [
        1 => ['id' => 1, 'district_id' => 1, 'code' => '00001', 'name' => 'Hang Bac', 'full_name' => 'Phuong Hang Bac', 'name_en' => 'Hang Bac Ward'],
        2 => ['id' => 2, 'district_id' => 1, 'code' => '00002', 'name' => 'Hang Dao', 'full_name' => 'Phuong Hang Dao', 'name_en' => 'Hang Dao Ward'],
        3 => ['id' => 3, 'district_id' => 2, 'code' => '00010', 'name' => 'Cong Vi', 'full_name' => 'Phuong Cong Vi', 'name_en' => 'Cong Vi Ward'],
        4 => ['id' => 4, 'district_id' => 3, 'code' => '26734', 'name' => 'Ben Nghe', 'full_name' => 'Phuong Ben Nghe', 'name_en' => 'Ben Nghe Ward'],
        5 => ['id' => 5, 'district_id' => 3, 'code' => '26737', 'name' => 'Ben Thanh', 'full_name' => 'Phuong Ben Thanh', 'name_en' => 'Ben Thanh Ward'],
    ];

    public function __construct() {}

    public function findProvinces(array $filters = []): array
    {
        return array_values($this->provinceData);
    }

    public function findProvinceById(int $id): ?array
    {
        return $this->provinceData[$id] ?? null;
    }

    public function findDistricts(int $provinceId, array $filters = []): array
    {
        return array_values(array_filter($this->districtData, fn($d) => $d['province_id'] === $provinceId));
    }

    public function findDistrictById(int $id): ?array
    {
        return $this->districtData[$id] ?? null;
    }

    public function findWards(int $districtId, array $filters = []): array
    {
        return array_values(array_filter($this->wardData, fn($w) => $w['district_id'] === $districtId));
    }

    public function countProvinces(): int
    {
        return count($this->provinceData);
    }

    public function countDistricts(): int
    {
        return count($this->districtData);
    }

    public function countWards(): int
    {
        return count($this->wardData);
    }
}
