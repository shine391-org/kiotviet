<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate POS offline queue payloads.
 *
 * @agent-validator: POS offline
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class POSOfflineValidator
{
    /**
     * Validate batch items.
     *
     * @agent-use: POS offline batch API
     * @agent-pattern: Batch validation
     */
    public function validateBatch(array $input): array
    {
        $items = $input['items'] ?? $input;
        if (! is_array($items) || empty($items)) {
            throw new InvalidArgumentException('items is required for offline sync');
        }
        $normalized = [];
        foreach ($items as $item) {
            $normalized[] = $this->validateItem($item);
        }
        return $normalized;
    }

    /**
     * Validate a single queue item.
     *
     * @agent-use: POS offline enqueue/sync
     * @agent-pattern: Required temp_id + device_id
     */
    public function validateItem(array $item): array
    {
        $tempId = isset($item['temp_id']) ? trim((string) $item['temp_id']) : '';
        if ($tempId === '') {
            throw new InvalidArgumentException('temp_id is required');
        }
        $deviceId = isset($item['device_id']) ? trim((string) $item['device_id']) : '';
        if ($deviceId === '') {
            throw new InvalidArgumentException('device_id is required');
        }

        $payload = $item['payload'] ?? null;
        if (! is_array($payload)) {
            throw new InvalidArgumentException('payload must be array');
        }

        $userId = isset($item['user_id']) ? (int) $item['user_id'] : null;
        if ($userId !== null && $userId <= 0) {
            throw new InvalidArgumentException('user_id must be positive');
        }
        $branchId = isset($item['branch_id']) ? (int) $item['branch_id'] : null;
        if ($branchId !== null && $branchId <= 0) {
            throw new InvalidArgumentException('branch_id must be positive');
        }

        return [
            'temp_id' => $tempId,
            'device_id' => $deviceId,
            'user_id' => $userId,
            'branch_id' => $branchId,
            'payload' => $payload,
        ];
    }
}
