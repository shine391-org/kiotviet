<?php

namespace App\Services\POS;

use App\Repositories\POS\POSOfflineQueueRepository;
use App\Validators\POSOfflineValidator;
use App\Services\Orders\OrderService;

/**
 * @agent-service: POS offline queue + sync
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class POSOfflineService
{
    protected POSOfflineQueueRepository $repo;
    protected POSOfflineValidator $validator;
    protected OrderService $orders;

    public function __construct(
        ?POSOfflineQueueRepository $repo = null,
        ?POSOfflineValidator $validator = null,
        ?OrderService $orders = null
    ) {
        $this->repo = $repo ?? new POSOfflineQueueRepository();
        $this->validator = $validator ?? new POSOfflineValidator();
        $this->orders = $orders ?? service('orderService');
    }

    /**
     * Enqueue items without processing.
     *
     * @agent-use: POST /api/pos/offline/queue
     * @agent-pattern: Queue only
     */
    public function enqueue(array $input): array
    {
        $items = $this->validator->validateBatch($input);
        $results = [];
        foreach ($items as $item) {
            $row = $this->repo->upsert($item + ['status' => 'pending']);
            $results[] = [
                'temp_id' => $row['temp_id'],
                'device_id' => $row['device_id'],
                'status' => $row['status'],
                'id' => $row['id'],
            ];
        }
        return ['success' => true, 'data' => $results];
    }

    /**
     * Sync a batch (or pending) items to orders with idempotency.
     *
     * @agent-use: POST /api/pos/offline/sync
     * @agent-pattern: Per-item result with partial success
     */
    public function sync(array $input = []): array
    {
        $items = [];
        if (! empty($input)) {
            $items = $this->validator->validateBatch($input);
        } else {
            $items = $this->repo->pending();
            if (empty($items)) {
                return ['success' => true, 'data' => []];
            }
        }

        $results = [];
        foreach ($items as $item) {
            $record = $this->repo->upsert($item + ['status' => 'pending']);
            if (! empty($record['order_id'])) {
                $results[] = [
                    'temp_id' => $record['temp_id'],
                    'device_id' => $record['device_id'],
                    'status' => 'synced',
                    'order_id' => $record['order_id'],
                ];
                continue;
            }

            try {
                $order = $this->orders->create($record['payload']);
                $orderId = $order['data']['id'] ?? ($order['id'] ?? null);
                $this->repo->markSynced($record['id'], $orderId);
                $results[] = [
                    'temp_id' => $record['temp_id'],
                    'device_id' => $record['device_id'],
                    'status' => 'synced',
                    'order_id' => $orderId,
                ];
            } catch (\Throwable $e) {
                $this->repo->markFailed($record['id'], $e->getMessage());
                $results[] = [
                    'temp_id' => $record['temp_id'],
                    'device_id' => $record['device_id'],
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return ['success' => true, 'data' => $results];
    }
}
