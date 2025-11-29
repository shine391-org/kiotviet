<?php

namespace App\Repositories\Accounting;

use App\Models\ExchangeRateModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Exchange rates
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class ExchangeRateRepository
{
    protected ExchangeRateModel $model;
    protected BaseConnection $db;

    public function __construct(?ExchangeRateModel $model = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->model = $model ?? new ExchangeRateModel();
    }

    public function create(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->model->insert($payload);
        $payload['id'] = (int) $this->model->getInsertID();
        return $payload;
    }

    public function latestRate(string $currency, string $asOf): ?array
    {
        $row = $this->model->where('currency', $currency)
            ->where('valid_from <=', $asOf)
            ->orderBy('valid_from', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();
        return $row ? $this->hydrate($row) : null;
    }

    public function list(): array
    {
        $rows = $this->model->orderBy('currency')->orderBy('valid_from', 'DESC')->findAll();
        return array_map(fn ($row) => $this->hydrate($row), $rows);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['rate'] = isset($row['rate']) ? (float) $row['rate'] : 0.0;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
