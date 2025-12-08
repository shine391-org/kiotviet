<?php

namespace App\Transformers;

/**
 * Transform invoice to shipment format for FE.
 *
 * @agent-transformer: Shipment formatter
 */
class ShipmentTransformer
{
    private array $partnerNames = [
        'manual' => 'Tự giao',
        'ghn' => 'Giao hàng nhanh',
        'ghtk' => 'Giao hàng tiết kiệm',
        'ahamove' => 'AhaMove',
        'xanh_sm' => 'Xanh SM',
        'grab' => 'Grab Express',
        'ninja_van' => 'Ninja Van',
        'j&t' => 'J&T Express',
        'viettel_post' => 'Viettel Post',
        'vnpost' => 'VN Post',
    ];

    public function transform(array $row): array
    {
        $partner = $row['delivery_partner'] ?? 'manual';
        $codAmount = (float) ($row['cod_amount'] ?? 0);
        $codCollected = (float) ($row['cod_collected'] ?? 0);

        return [
            'id' => (int) ($row['id'] ?? 0),
            'code' => $row['code'] ?? $this->generateCode($row),
            'invoice_code' => $row['invoice_code'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'customer_name' => $row['customer_name'] ?? 'Khách lẻ',
            'branch_id' => isset($row['branch_id']) ? (int) $row['branch_id'] : null,
            'branch_name' => $row['branch_name'] ?? null,
            'delivery_partner' => $partner,
            'delivery_partner_name' => $this->partnerNames[$partner] ?? ucfirst($partner),
            'delivery_status' => $row['delivery_status'] ?? 'pending',
            'delivery_time' => $row['delivery_time'] ?? null,
            'cod_amount' => $codAmount,
            'cod_remaining' => max(0, $codAmount - $codCollected),
            'partner_fee' => (float) ($row['partner_fee'] ?? 0),
            'partner_fee_due' => (float) ($row['partner_fee'] ?? 0),
            'status_note' => $row['status_note'] ?? '',
            'recipient' => $row['customer_name'] ?? '',
            'phone' => $row['phone'] ?? '',
            'address' => $row['address'] ?? '',
            'area_path' => $this->buildAreaPath($row),
            'ward' => $row['ward'] ?? '',
            'service' => 'Giao thường',
            'weight' => 0,
            'weight_unit' => 'g',
            'dimensions' => ['length' => 10, 'width' => 10, 'height' => 10],
            'created_by' => $row['created_by'] ?? null,
            'delivery_history' => $this->buildDeliveryHistory($row),
        ];
    }

    public function transformList(array $rows): array
    {
        return array_map(fn ($r) => $this->transform($r), $rows);
    }

    private function generateCode(array $row): string
    {
        $id = $row['id'] ?? 0;
        return strtoupper(substr(md5((string) $id . ($row['created_at'] ?? '')), 0, 8));
    }

    private function buildAreaPath(array $row): array
    {
        $path = [];
        if (! empty($row['area_province'])) {
            $path[] = $row['area_province'];
        }
        if (! empty($row['area_district'])) {
            $path[] = $row['area_district'];
        }
        return $path;
    }

    private function buildDeliveryHistory(array $row): array
    {
        $history = [];
        if (! empty($row['created_at'])) {
            $history[] = [
                'time' => $row['created_at'],
                'partner' => $row['delivery_partner'] ?? 'manual',
                'status' => 'pending',
                'creator' => $row['created_by'] ?? null,
            ];
        }
        if (! empty($row['delivery_time']) && ($row['delivery_status'] ?? '') === 'delivered') {
            $history[] = [
                'time' => $row['delivery_time'],
                'partner' => $row['delivery_partner'] ?? 'manual',
                'status' => 'delivered',
                'creator' => null,
            ];
        }
        return $history;
    }
}
