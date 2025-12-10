<?php

namespace App\Services\Shipping;

use App\Repositories\Shipping\ShippingRepository;
use InvalidArgumentException;
use RuntimeException;

class ShippingService
{
    protected ShippingRepository $repo;

    public function __construct(?ShippingRepository $repo = null)
    {
        $this->repo = $repo ?? new ShippingRepository();
    }

    /** Calculate shipping fee */
    public function calculateFee(array $params): array
    {
        $provinceId = $params['province_id'] ?? null;
        $districtId = $params['district_id'] ?? null;
        $weight = (float) ($params['weight'] ?? 0);
        $orderValue = (float) ($params['order_value'] ?? 0);
        $partnerId = $params['delivery_partner_id'] ?? null;

        // Find zone by location
        $zone = $this->repo->findZoneByLocation($provinceId, $districtId);
        if (!$zone) {
            return [
                'success' => true,
                'data' => [
                    'shipping_fee' => 0,
                    'zone' => null,
                    'free_shipping' => false,
                    'message' => 'No shipping zone configured for this location',
                ],
            ];
        }

        // Find applicable rate
        $rate = $this->repo->findApplicableRate((int) $zone['id'], $weight, $orderValue, $partnerId);
        if (!$rate) {
            return [
                'success' => true,
                'data' => [
                    'shipping_fee' => 0,
                    'zone' => $zone['name'],
                    'free_shipping' => false,
                    'message' => 'No shipping rate configured for this weight/value',
                ],
            ];
        }

        // Calculate fee
        $baseFee = (float) $rate['base_fee'];
        $perKgFee = (float) $rate['per_kg_fee'];
        $freeThreshold = $rate['free_shipping_threshold'] ? (float) $rate['free_shipping_threshold'] : null;

        $shippingFee = $baseFee + ($weight * $perKgFee);
        $freeShipping = $freeThreshold !== null && $orderValue >= $freeThreshold;

        if ($freeShipping) {
            $shippingFee = 0;
        }

        return [
            'success' => true,
            'data' => [
                'shipping_fee' => round($shippingFee, 0),
                'zone' => $zone['name'],
                'zone_id' => (int) $zone['id'],
                'rate_id' => (int) $rate['id'],
                'free_shipping' => $freeShipping,
                'free_shipping_threshold' => $freeThreshold,
                'base_fee' => $baseFee,
                'per_kg_fee' => $perKgFee,
                'weight' => $weight,
                'order_value' => $orderValue,
            ],
        ];
    }

    /** List shipping zones */
    public function listZones(array $filters = []): array
    {
        $zones = $this->repo->findAllZones($filters);
        return [
            'success' => true,
            'data' => array_map(fn($z) => [
                'id' => (int) $z['id'],
                'name' => $z['name'],
                'province_ids' => is_string($z['province_ids']) ? json_decode($z['province_ids'], true) : ($z['province_ids'] ?? []),
                'district_ids' => is_string($z['district_ids']) ? json_decode($z['district_ids'], true) : ($z['district_ids'] ?? []),
                'is_active' => (bool) $z['is_active'],
            ], $zones),
        ];
    }

    /** Create shipping zone */
    public function createZone(array $data): array
    {
        if (empty($data['name'])) {
            throw new InvalidArgumentException('Zone name is required');
        }
        $zone = $this->repo->createZone([
            'name' => $data['name'],
            'province_ids' => json_encode($data['province_ids'] ?? []),
            'district_ids' => json_encode($data['district_ids'] ?? []),
            'is_active' => $data['is_active'] ?? 1,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        return ['success' => true, 'data' => $zone, 'message' => 'Zone created'];
    }

    /** Update shipping zone */
    public function updateZone(int $id, array $data): array
    {
        if (!$this->repo->findZoneById($id)) {
            throw new RuntimeException('Zone not found');
        }
        $update = [];
        if (isset($data['name'])) $update['name'] = $data['name'];
        if (isset($data['province_ids'])) $update['province_ids'] = json_encode($data['province_ids']);
        if (isset($data['district_ids'])) $update['district_ids'] = json_encode($data['district_ids']);
        if (isset($data['is_active'])) $update['is_active'] = $data['is_active'];
        if (isset($data['sort_order'])) $update['sort_order'] = $data['sort_order'];

        $zone = $this->repo->updateZone($id, $update);
        return ['success' => true, 'data' => $zone, 'message' => 'Zone updated'];
    }

    /** Delete zone */
    public function deleteZone(int $id): array
    {
        if (!$this->repo->findZoneById($id)) {
            throw new RuntimeException('Zone not found');
        }
        $this->repo->deleteZone($id);
        return ['success' => true, 'message' => 'Zone deleted'];
    }

    /** Get rates for zone */
    public function getRates(int $zoneId): array
    {
        $rates = $this->repo->findRatesByZone($zoneId);
        return ['success' => true, 'data' => $rates];
    }

    /** Create shipping rate */
    public function createRate(array $data): array
    {
        if (empty($data['zone_id'])) {
            throw new InvalidArgumentException('Zone ID is required');
        }
        $rate = $this->repo->createRate([
            'zone_id' => $data['zone_id'],
            'delivery_partner_id' => $data['delivery_partner_id'] ?? null,
            'min_weight' => $data['min_weight'] ?? 0,
            'max_weight' => $data['max_weight'] ?? 999999,
            'min_value' => $data['min_value'] ?? 0,
            'max_value' => $data['max_value'] ?? 999999999,
            'base_fee' => $data['base_fee'] ?? 0,
            'per_kg_fee' => $data['per_kg_fee'] ?? 0,
            'free_shipping_threshold' => $data['free_shipping_threshold'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
        ]);
        return ['success' => true, 'data' => $rate, 'message' => 'Rate created'];
    }

    /** Update shipping rate */
    public function updateRate(int $id, array $data): array
    {
        if (!$this->repo->findRateById($id)) {
            throw new RuntimeException('Rate not found');
        }
        $allowed = ['zone_id', 'delivery_partner_id', 'min_weight', 'max_weight', 'min_value', 'max_value', 'base_fee', 'per_kg_fee', 'free_shipping_threshold', 'is_active'];
        $update = array_intersect_key($data, array_flip($allowed));
        $rate = $this->repo->updateRate($id, $update);
        return ['success' => true, 'data' => $rate, 'message' => 'Rate updated'];
    }

    /** Delete rate */
    public function deleteRate(int $id): array
    {
        if (!$this->repo->findRateById($id)) {
            throw new RuntimeException('Rate not found');
        }
        $this->repo->deleteRate($id);
        return ['success' => true, 'message' => 'Rate deleted'];
    }
}
