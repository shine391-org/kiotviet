<?php

namespace App\Repositories\POS;

use App\Models\POSShiftLogModel;
use App\Models\POSShiftModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: POS shifts
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class POSShiftRepository
{
    protected POSShiftModel $shifts;
    protected POSShiftLogModel $logs;
    protected POSShiftPaymentRepository $payments;
    protected BaseConnection $db;

    public function __construct(
        ?POSShiftModel $shifts = null,
        ?POSShiftLogModel $logs = null,
        ?POSShiftPaymentRepository $payments = null,
        ?BaseConnection $db = null
    ) {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->shifts = $shifts ?? new POSShiftModel();
        $this->logs = $logs ?? new POSShiftLogModel();
        $this->payments = $payments ?? new POSShiftPaymentRepository(null, $this->db);
    }

    public function create(array $data): array
    {
        $payload = $this->encode($data) + [
            'opened_at' => $data['opened_at'] ?? $this->now(),
            'status' => $data['status'] ?? 'open',
            'created_at' => $data['created_at'] ?? $this->now(),
            'updated_at' => $data['updated_at'] ?? $this->now(),
        ];
        $this->shifts->insert($payload);
        $id = (int) $this->shifts->getInsertID();
        $this->log($id, 'open', 'Shift opened');
        return $this->findById($id) ?? ($payload + ['id' => $id]);
    }

    public function updateShift(int $id, array $data): bool
    {
        $payload = $this->encode($data) + ['updated_at' => $this->now()];
        return (bool) $this->shifts->update($id, $payload);
    }

    public function close(int $id, array $data): bool
    {
        $payload = $this->encode($data) + [
            'status' => 'closed',
            'closed_at' => $data['closed_at'] ?? $this->now(),
            'updated_at' => $this->now(),
        ];
        $updated = (bool) $this->shifts->update($id, $payload);
        if ($updated) {
            $this->log($id, 'close', $data['closing_note'] ?? 'Shift closed');
        }
        return $updated;
    }

    public function findById(int $id): ?array
    {
        $row = $this->shifts->find($id);
        return $row ? $this->hydrate($row) : null;
    }

    public function findOpenByUser(int $userId, ?int $branchId = null): ?array
    {
        $builder = $this->shifts->builder()
            ->where('user_id', $userId)
            ->where('status', 'open');
        if ($branchId) {
            $builder->where('branch_id', $branchId);
        }
        $row = $builder->orderBy('opened_at', 'DESC')->get(1)->getRowArray();
        return $row ? $this->hydrate($row) : null;
    }

    public function log(int $shiftId, string $action, ?string $message = null): void
    {
        $this->logs->insert([
            'shift_id' => $shiftId,
            'action' => $action,
            'message' => $message,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);
    }

    public function logPayment(int $shiftId, array $payment): int
    {
        return $this->payments->log([
            'shift_id' => $shiftId,
            'order_id' => $payment['order_id'] ?? null,
            'payment_method' => strtoupper((string) ($payment['payment_method'] ?? '')),
            'amount' => (float) ($payment['amount'] ?? 0),
            'reference_type' => $payment['reference_type'] ?? 'order',
            'reference_id' => $payment['reference_id'] ?? null,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);
    }

    public function paymentsByMethod(int $shiftId): array
    {
        return $this->payments->sumByMethod($shiftId);
    }

    private function encode(array $data): array
    {
        foreach (['opening_balance','expected_total','expected_cash','expected_card','actual_total','actual_cash','actual_card','discrepancy'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = (float) $data[$field];
            }
        }
        return $data;
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['user_id'] = isset($row['user_id']) ? (int) $row['user_id'] : null;
        $row['profile_id'] = isset($row['profile_id']) ? (int) $row['profile_id'] : null;
        $row['branch_id'] = isset($row['branch_id']) ? (int) $row['branch_id'] : null;
        foreach (['opening_balance','expected_total','expected_cash','expected_card','actual_total','actual_cash','actual_card','discrepancy'] as $field) {
            if (isset($row[$field])) {
                $row[$field] = (float) $row[$field];
            }
        }
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
