<?php

namespace App\Repositories\Shipping;

use App\Models\ShippingZoneModel;
use App\Models\ShippingRateModel;
use CodeIgniter\Database\BaseConnection;

class ShippingRepository
{
    protected ShippingZoneModel $zoneModel;
    protected ShippingRateModel $rateModel;
    protected BaseConnection $db;

    public function __construct()
    {
        $this->zoneModel = new ShippingZoneModel();
        $this->rateModel = new ShippingRateModel();
        $this->db = \Config\Database::connect();
    }

    public function findAllZones(array $filters = []): array
    {
        $builder = $this->zoneModel->builder();
        if (isset($filters['is_active'])) {
            $builder->where('is_active', $filters['is_active']);
        }
        return $builder->orderBy('sort_order', 'ASC')->get()->getResultArray();
    }

    public function findZoneById(int $id): ?array
    {
        return $this->zoneModel->find($id);
    }

    public function createZone(array $data): ?array
    {
        $this->zoneModel->insert($data);
        return $this->findZoneById((int) $this->zoneModel->getInsertID());
    }

    public function updateZone(int $id, array $data): ?array
    {
        $this->zoneModel->update($id, $data);
        return $this->findZoneById($id);
    }

    public function deleteZone(int $id): bool
    {
        return $this->zoneModel->delete($id);
    }

    public function findRatesByZone(int $zoneId): array
    {
        return $this->rateModel->where('zone_id', $zoneId)->where('is_active', 1)->findAll();
    }

    public function findZoneByLocation(?int $provinceId, ?int $districtId): ?array
    {
        $zones = $this->findAllZones(['is_active' => 1]);

        foreach ($zones as $zone) {
            $provinceIds = is_string($zone['province_ids']) ? json_decode($zone['province_ids'], true) : ($zone['province_ids'] ?? []);
            $districtIds = is_string($zone['district_ids']) ? json_decode($zone['district_ids'], true) : ($zone['district_ids'] ?? []);

            // Check district first (more specific)
            if ($districtId && !empty($districtIds) && in_array($districtId, $districtIds)) {
                return $zone;
            }
            // Then check province
            if ($provinceId && !empty($provinceIds) && in_array($provinceId, $provinceIds)) {
                return $zone;
            }
        }

        // Return default zone (first with empty province/district)
        foreach ($zones as $zone) {
            $provinceIds = is_string($zone['province_ids']) ? json_decode($zone['province_ids'], true) : ($zone['province_ids'] ?? []);
            $districtIds = is_string($zone['district_ids']) ? json_decode($zone['district_ids'], true) : ($zone['district_ids'] ?? []);
            if (empty($provinceIds) && empty($districtIds)) {
                return $zone;
            }
        }

        return null;
    }

    public function findApplicableRate(int $zoneId, float $weight, float $orderValue, ?int $partnerId = null): ?array
    {
        $builder = $this->rateModel->builder()
            ->where('zone_id', $zoneId)
            ->where('is_active', 1)
            ->where('min_weight <=', $weight)
            ->where('max_weight >=', $weight)
            ->where('min_value <=', $orderValue)
            ->where('max_value >=', $orderValue);

        if ($partnerId) {
            $builder->where('delivery_partner_id', $partnerId);
        }

        return $builder->orderBy('base_fee', 'ASC')->get()->getRowArray();
    }

    public function findRateById(int $id): ?array
    {
        return $this->rateModel->find($id);
    }

    public function createRate(array $data): ?array
    {
        $this->rateModel->insert($data);
        return $this->rateModel->find((int) $this->rateModel->getInsertID());
    }

    public function updateRate(int $id, array $data): ?array
    {
        $this->rateModel->update($id, $data);
        return $this->rateModel->find($id);
    }

    public function deleteRate(int $id): bool
    {
        return $this->rateModel->delete($id);
    }
}
