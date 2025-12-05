<?php

namespace App\Repositories\Payments;

use App\Models\PaymentEntryModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Payment entries
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class PaymentEntryRepository
{
    protected PaymentEntryModel $model;
    protected BaseConnection $db;

    public function __construct(?PaymentEntryModel $model = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->model = $model ?? new PaymentEntryModel();
    }

    public function findByKey(int $orderId, string $method, ?string $reference): ?array
    {
        $builder = $this->model->builder()
            ->where('order_id', $orderId)
            ->where('payment_method', $method);
        if ($reference === null) {
            $builder->where('reference IS NULL', null, false);
        } else {
            $builder->where('reference', $reference);
        }
        $row = $builder->get()->getRowArray();
        return $row ? $this->hydrate($row) : null;
    }

    public function create(array $data): array
    {
        $payload = $this->encode($data) + [
            'created_at' => $data['created_at'] ?? $this->now(),
            'updated_at' => $data['updated_at'] ?? $this->now(),
        ];
        $this->model->insert($payload);
        $payload['id'] = (int) $this->model->getInsertID();
        return $this->hydrate($payload);
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->model->update($id, ['status' => $status, 'updated_at' => $this->now()]);
    }

    private function encode(array $data): array
    {
        $data['payment_method'] = strtoupper($data['payment_method']);
        if (! isset($data['reference']) || $data['reference'] === null) {
            $data['reference'] = '';
        }
        return $data;
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['order_id'] = isset($row['order_id']) ? (int) $row['order_id'] : null;
        $row['amount'] = isset($row['amount']) ? (float) $row['amount'] : 0.0;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
