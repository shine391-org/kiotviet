<?php

namespace App\Repositories\Accounting;

use App\Models\BankStatementModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Bank statements
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class BankStatementRepository
{
    protected BankStatementModel $model;
    protected BaseConnection $db;

    public function __construct(?BankStatementModel $model = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->model = $model ?? new BankStatementModel();
    }

    public function import(array $lines): array
    {
        $now = date('Y-m-d H:i:s');
        $payloads = [];
        foreach ($lines as $line) {
            $payloads[] = $line + ['created_at' => $now, 'updated_at' => $now];
        }
        $this->model->insertBatch($payloads);
        return $this->model->where('created_at', $now)->findAll();
    }

    public function list(array $filters = []): array
    {
        $builder = $this->model;
        if (! empty($filters['status'])) {
            $builder = $builder->where('status', $filters['status']);
        }
        return $builder->orderBy('id', 'DESC')->findAll();
    }
}
