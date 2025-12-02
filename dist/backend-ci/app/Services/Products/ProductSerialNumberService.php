<?php

namespace App\Services\Products;

use App\Repositories\Products\ProductBatchRepository;
use App\Repositories\Products\ProductSerialNumberRepository;
use App\Validators\ProductSerialValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Product serial number business logic.
 *
 * @agent-service: Product serial numbers
 * @agent-pattern: Service orchestrator
 * @agent-reusable: HIGH
 */
class ProductSerialNumberService
{
    protected ProductSerialNumberRepository $repo;
    protected ProductSerialValidator $validator;
    protected ProductBatchRepository $batchRepo;

    public function __construct(
        ?ProductSerialNumberRepository $repo = null,
        ?ProductSerialValidator $validator = null,
        ?ProductBatchRepository $batchRepo = null
    ) {
        $this->repo = $repo ?? new ProductSerialNumberRepository();
        $this->validator = $validator ?? new ProductSerialValidator();
        $this->batchRepo = $batchRepo ?? new ProductBatchRepository();
    }

    /** List serial numbers. @agent-use: GET /api/product-serials */
    public function list(array $filters): array
    {
        $validated = $this->validator->validateFilters($filters);
        return ['success' => true, 'data' => $this->repo->list($validated)];
    }

    /** Create serial number record. */
    public function create(array $data): array
    {
        $payload = $this->validator->validateCreate($data);
        $batchId = isset($payload['batch_id']) ? (int) $payload['batch_id'] : null;
        if ($batchId) {
            $batch = $this->batchRepo->find($batchId);
            if (! $batch) {
                throw new RuntimeException('Batch not found for serial number');
            }
            if ((int) $batch['product_id'] !== (int) $payload['product_id']) {
                throw new InvalidArgumentException('Batch does not belong to the product');
            }
            if (! isset($payload['variant_id']) && isset($batch['variant_id'])) {
                $payload['variant_id'] = $batch['variant_id'];
            }
        }
        if ($this->repo->findBySerial($payload['serial_number'])) {
            throw new InvalidArgumentException('Serial number already exists');
        }

        $serial = $this->repo->create($payload);
        return ['success' => true, 'data' => $serial];
    }

    /** Reserve serials for an order. */
    public function reserve(array $data): array
    {
        $payload = $this->validator->validateReserve($data);
        $db = $this->repo->db();
        $db->transBegin();
        try {
            $rows = [];
            foreach ($payload['serial_numbers'] as $serial) {
                $this->ensureAvailableForOrder($serial, $payload['order_id']);
                $rows[] = $this->repo->reserve($serial, $payload['order_id']);
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }

        return ['success' => true, 'data' => $rows];
    }

    /** Mark serials as sold for an order. */
    public function sell(array $data): array
    {
        $payload = $this->validator->validateSell($data);
        $db = $this->repo->db();
        $db->transBegin();
        try {
            $rows = [];
            foreach ($payload['serial_numbers'] as $serial) {
                $this->ensureAvailableForOrder($serial, $payload['order_id']);
                $rows[] = $this->repo->markSold($serial, $payload['order_id']);
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
        return ['success' => true, 'data' => $rows];
    }

    /** Mark serials as returned. */
    public function markReturned(array $data): array
    {
        $payload = $this->validator->validateReturn($data);
        $db = $this->repo->db();
        $db->transBegin();
        try {
            $rows = [];
            foreach ($payload['serial_numbers'] as $serial) {
                $rows[] = $this->repo->markReturned($serial, $payload['order_id']);
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
        return ['success' => true, 'data' => $rows];
    }

    /** Release reservations for provided serials. */
    public function release(array $serialNumbers): void
    {
        if (empty($serialNumbers)) {
            return;
        }
        $db = $this->repo->db();
        $db->transBegin();
        try {
            foreach ($serialNumbers as $serial) {
                $this->repo->release($serial);
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Ensure serial belongs to product/variant and is available for order context.
     *
     * @throws RuntimeException
     */
    public function ensureAvailableForOrder(string $serialNumber, ?int $orderId = null): array
    {
        $serial = $this->repo->findBySerial($serialNumber);
        if (! $serial) {
            throw new RuntimeException('Serial number not found');
        }
        $status = $serial['status'] ?? 'available';
        if ($status === 'sold') {
            throw new RuntimeException('Serial already sold');
        }
        if ($status === 'reserved' && $orderId && (int) ($serial['reserved_for_order_id'] ?? 0) !== $orderId) {
            throw new RuntimeException('Serial reserved by another order');
        }
        return $serial;
    }
}
