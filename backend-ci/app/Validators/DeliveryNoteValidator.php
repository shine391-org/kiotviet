<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate delivery note payloads.
 *
 * @agent-validator: Delivery note
 * @agent-pattern: Validation first
 * @agent-reusable: HIGH
 */
class DeliveryNoteValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    /** Validate creation request. @agent-use: POST /api/delivery-notes */
    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'order_id' => 'permit_empty|integer|greater_than[0]',
            'customer_id' => 'permit_empty|integer|greater_than[0]',
            'branch_id' => 'required|integer|greater_than[0]',
            'delivery_date' => 'permit_empty|valid_date[Y-m-d]',
            'expected_delivery_date' => 'permit_empty|valid_date[Y-m-d]',
            'shipping_address' => 'permit_empty|string',
            'notes' => 'permit_empty|string',
            'tracking_number' => 'permit_empty|string|max_length[120]',
            'carrier' => 'permit_empty|string|max_length[120]',
            'items' => 'permit_empty',
        ]);
        $items = $this->normalizeItems($input['items'] ?? []);
        if (! $items) {
            throw new InvalidArgumentException('items is required');
        }
        $data['items'] = $items;
        $data['delivery_date'] = $data['delivery_date'] ?? date('Y-m-d');
        $data['status'] = 'draft';
        return $data;
    }

    /** Validate create-from-order payload. */
    public function validateCreateFromOrder(array $input): array
    {
        $data = $this->run($input, [
            'order_id' => 'required|integer|greater_than[0]',
            'branch_id' => 'required|integer|greater_than[0]',
            'delivery_date' => 'permit_empty|valid_date[Y-m-d]',
            'expected_delivery_date' => 'permit_empty|valid_date[Y-m-d]',
            'shipping_address' => 'permit_empty|string',
            'notes' => 'permit_empty|string',
        ]);
        $data['delivery_date'] = $data['delivery_date'] ?? date('Y-m-d');
        return $data;
    }

    /** Validate status transition payload. */
    public function validateStatus(array $input, array $allowedStatuses): string
    {
        $data = $this->run($input, [
            'status' => 'required|string|max_length[30]',
        ]);
        $status = $data['status'];
        if (! in_array($status, $allowedStatuses, true)) {
            throw new InvalidArgumentException('Invalid status transition');
        }
        return $status;
    }

    /** Validate deliver payload with per-item quantities. */
    public function validateDeliver(array $input): array
    {
        $data = $this->run($input, [
            'delivered_by' => 'permit_empty|integer|greater_than_equal_to[0]',
            'items' => 'required',
        ]);
        $items = $this->normalizeDeliveredItems($input['items']);
        if (! $items) {
            throw new InvalidArgumentException('items is required');
        }
        $data['items'] = $items;
        return $data;
    }

    /** Validate shipping update. */
    public function validateShip(array $input): array
    {
        return $this->run($input, [
            'tracking_number' => 'permit_empty|string|max_length[120]',
            'carrier' => 'permit_empty|string|max_length[120]',
            'notes' => 'permit_empty|string',
        ]);
    }

    private function normalizeItems(array $items): array
    {
        if (! is_array($items)) {
            return [];
        }
        $normalized = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (float) ($item['quantity'] ?? 0);
            if ($productId <= 0 || $qty <= 0) {
                throw new InvalidArgumentException('product_id and quantity are required');
            }
            $serials = $this->serialNumbersFromInput($item['serial_numbers'] ?? []);
            $normalized[] = [
                'order_item_id' => isset($item['order_item_id']) ? (int) $item['order_item_id'] : null,
                'product_id' => $productId,
                'variant_id' => isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                'batch_id' => isset($item['batch_id']) ? (int) $item['batch_id'] : null,
                'serial_number' => $serials ? implode(',', $serials) : null,
                'quantity' => $qty,
                'delivered_quantity' => isset($item['delivered_quantity']) ? (float) $item['delivered_quantity'] : 0,
                'notes' => $item['notes'] ?? null,
            ];
        }
        return $normalized;
    }

    private function normalizeDeliveredItems($items): array
    {
        if (! is_array($items)) {
            return [];
        }
        $normalized = [];
        foreach ($items as $item) {
            $itemId = (int) ($item['delivery_note_item_id'] ?? 0);
            $qty = (float) ($item['quantity'] ?? 0);
            if ($itemId <= 0) {
                throw new InvalidArgumentException('delivery_note_item_id is required');
            }
            if ($qty <= 0) {
                throw new InvalidArgumentException('quantity must be > 0');
            }
            $normalized[] = [
                'delivery_note_item_id' => $itemId,
                'quantity' => $qty,
            ];
        }
        return $normalized;
    }

    private function serialNumbersFromInput($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = array_map('trim', explode(',', $value));
            }
        }
        if (! is_array($value)) {
            return [];
        }
        $serials = array_map(static fn ($s) => is_numeric($s) ? (string) $s : (is_string($s) ? trim($s) : ''), $value);
        $serials = array_filter($serials, static fn ($s) => $s !== '');
        return array_values(array_unique($serials));
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return $this->v->getValidated();
    }
}
