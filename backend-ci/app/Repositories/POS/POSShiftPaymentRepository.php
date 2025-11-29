<?php

namespace App\Repositories\POS;

use App\Models\POSShiftPaymentModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: POS shift payments
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class POSShiftPaymentRepository
{
    protected POSShiftPaymentModel $model;
    protected BaseConnection $db;

    public function __construct(?POSShiftPaymentModel $model = null, ?BaseConnection $db = null)
    {
        $this->model = $model ?? new POSShiftPaymentModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function log(array $data): int
    {
        $payload = $data + [
            'created_at' => $data['created_at'] ?? $this->now(),
            'updated_at' => $data['updated_at'] ?? $this->now(),
        ];
        $this->model->insert($payload);
        return (int) $this->model->getInsertID();
    }

    public function sumByMethod(int $shiftId): array
    {
        $builder = $this->db->table('pos_shift_payments')
            ->select('payment_method, SUM(amount) AS total')
            ->where('shift_id', $shiftId)
            ->groupBy('payment_method');
        $rows = $builder->get()->getResultArray();
        $result = [];
        foreach ($rows as $row) {
            $result[strtoupper((string) $row['payment_method'])] = (float) ($row['total'] ?? 0);
        }
        return $result;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
