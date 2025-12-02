<?php

namespace App\Repositories\Pricing;

use App\Models\PriceHistoryModel;

/**
 * Price history repository.
 *
 * @agent-repository: Price history
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class PriceHistoryRepository
{
    protected PriceHistoryModel $model;

    public function __construct(?PriceHistoryModel $model = null)
    {
        $this->model = $model ?? new PriceHistoryModel();
    }

    public function log(array $data): array
    {
        $payload = $data + ['changed_at' => date('Y-m-d H:i:s')];
        $this->model->insert($payload);
        $payload['id'] = (int) $this->model->getInsertID();
        return $payload;
    }
}
