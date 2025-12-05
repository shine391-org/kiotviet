<?php

namespace App\Repositories\Accounting;

use App\Models\PaymentEntryModel;
use App\Models\PaymentEntryAllocationModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Payment entries (accounting)
 * @agent-pattern: Repository with allocations
 * @agent-reusable: MEDIUM
 */
class PaymentEntryRepository
{
    protected PaymentEntryModel $entries;
    protected PaymentEntryAllocationModel $allocations;
    protected BaseConnection $db;

    public function __construct(
        ?PaymentEntryModel $entries = null,
        ?PaymentEntryAllocationModel $allocations = null,
        ?BaseConnection $db = null
    ) {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->entries = $entries ?? new PaymentEntryModel();
        $this->allocations = $allocations ?? new PaymentEntryAllocationModel();
    }

    public function create(array $data, array $allocations = []): array
    {
        $now = $this->now();
        $payload = $data + ['created_at' => $now, 'updated_at' => $now];
        $this->db->transStart();
        $this->entries->insert($payload);
        $id = (int) $this->entries->getInsertID();

        $allocRows = [];
        foreach ($allocations as $alloc) {
            $allocRows[] = $alloc + [
                'payment_entry_id' => $id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($allocRows) {
            $this->allocations->insertBatch($allocRows);
        }
        $this->db->transComplete();
        return $this->findById($id);
    }

    public function findById(int $id): ?array
    {
        $row = $this->entries->find($id);
        return $row ? $this->findByIdWithAllocations($row) : null;
    }

    private function findByIdWithAllocations(array $entry): array
    {
        $allocs = $this->allocations->where('payment_entry_id', (int) $entry['id'])->findAll();
        $entry = $this->hydrate($entry);
        $entry['allocations'] = array_map(fn ($a) => $this->hydrateAlloc($a), $allocs);
        return $entry;
    }

    public function updateStatus(int $id, string $status): bool
    {
        return (bool) $this->entries->update($id, ['status' => $status, 'updated_at' => $this->now()]);
    }

    public function findByReference(string $referenceNo, float $amount): ?array
    {
        $row = $this->entries->where('reference_no', $referenceNo)
            ->where('amount', $amount)
            ->first();
        return $row ? $this->findById((int) $row['id']) : null;
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['order_id'] = isset($row['order_id']) ? (int) $row['order_id'] : null;
        $row['party_id'] = isset($row['party_id']) ? (int) $row['party_id'] : null;
        $row['reference_id'] = isset($row['reference_id']) ? (int) $row['reference_id'] : null;
        $row['debit_account_id'] = isset($row['debit_account_id']) ? (int) $row['debit_account_id'] : null;
        $row['credit_account_id'] = isset($row['credit_account_id']) ? (int) $row['credit_account_id'] : null;
        $row['amount'] = isset($row['amount']) ? (float) $row['amount'] : 0.0;
        $row['exchange_rate'] = isset($row['exchange_rate']) ? (float) $row['exchange_rate'] : 1.0;
        return $row;
    }

    private function hydrateAlloc(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['payment_entry_id'] = isset($row['payment_entry_id']) ? (int) $row['payment_entry_id'] : null;
        $row['reference_id'] = isset($row['reference_id']) ? (int) $row['reference_id'] : null;
        $row['allocated_amount'] = isset($row['allocated_amount']) ? (float) $row['allocated_amount'] : 0.0;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
