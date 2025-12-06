<?php

namespace App\Services\Locations;

use App\Repositories\Locations\LocationRepository;
use RuntimeException;

/**
 * Location business logic.
 *
 * @agent-service: Locations
 * @agent-pattern: Service orchestrator
 */
class LocationService
{
    protected LocationRepository $repo;

    public function __construct(?LocationRepository $repo = null)
    {
        $this->repo = $repo ?? new LocationRepository();
    }

    /** List all provinces. */
    public function listProvinces(array $filters = []): array
    {
        $rows = $this->repo->findProvinces($filters);
        return [
            'success' => true,
            'data' => array_map([$this, 'transformProvince'], $rows),
            'total' => count($rows),
        ];
    }

    /** List districts by province. */
    public function listDistricts(int $provinceId, array $filters = []): array
    {
        $province = $this->repo->findProvinceById($provinceId);
        if (!$province) {
            throw new RuntimeException('Province not found');
        }

        $rows = $this->repo->findDistricts($provinceId, $filters);
        return [
            'success' => true,
            'data' => array_map([$this, 'transformDistrict'], $rows),
            'total' => count($rows),
            'province' => $this->transformProvince($province),
        ];
    }

    /** List wards by district. */
    public function listWards(int $districtId, array $filters = []): array
    {
        $district = $this->repo->findDistrictById($districtId);
        if (!$district) {
            throw new RuntimeException('District not found');
        }

        $rows = $this->repo->findWards($districtId, $filters);
        return [
            'success' => true,
            'data' => array_map([$this, 'transformWard'], $rows),
            'total' => count($rows),
            'district' => $this->transformDistrict($district),
        ];
    }

    /** Get location statistics. */
    public function getStats(): array
    {
        return [
            'success' => true,
            'data' => [
                'provinces' => $this->repo->countProvinces(),
                'districts' => $this->repo->countDistricts(),
                'wards' => $this->repo->countWards(),
            ],
        ];
    }

    private function transformProvince(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'code' => $row['code'],
            'name' => $row['name'],
            'full_name' => $row['full_name'] ?? $row['name'],
            'name_en' => $row['name_en'] ?? null,
        ];
    }

    private function transformDistrict(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'province_id' => (int) $row['province_id'],
            'code' => $row['code'],
            'name' => $row['name'],
            'full_name' => $row['full_name'] ?? $row['name'],
            'name_en' => $row['name_en'] ?? null,
        ];
    }

    private function transformWard(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'district_id' => (int) $row['district_id'],
            'code' => $row['code'],
            'name' => $row['name'],
            'full_name' => $row['full_name'] ?? $row['name'],
            'name_en' => $row['name_en'] ?? null,
        ];
    }
}
