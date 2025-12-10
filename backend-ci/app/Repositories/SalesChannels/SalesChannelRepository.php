<?php

namespace App\Repositories\SalesChannels;

use App\Models\SalesChannelModel;

class SalesChannelRepository
{
    protected SalesChannelModel $model;

    public function __construct(?SalesChannelModel $model = null)
    {
        $this->model = $model ?? new SalesChannelModel();
    }

    public function findAll(array $filters = []): array
    {
        $builder = $this->model->builder();

        if (isset($filters['is_active'])) {
            $builder->where('is_active', $filters['is_active']);
        }

        return $builder->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->get()->getResultArray();
    }

    public function findById(int $id): ?array
    {
        return $this->model->find($id);
    }

    public function findByCode(string $code): ?array
    {
        return $this->model->where('code', $code)->first();
    }

    public function create(array $data): ?array
    {
        $result = $this->model->insert($data);
        if ($result === false) {
            return null;
        }
        $insertId = (int) $this->model->getInsertID();
        if ($insertId <= 0) {
            return null;
        }
        return $this->findById($insertId);
    }

    public function update(int $id, array $data): ?array
    {
        $existing = $this->findById($id);
        if ($existing === null) {
            return null;
        }
        $result = $this->model->update($id, $data);
        if ($result === false) {
            return null;
        }
        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return $this->model->delete($id);
    }
}
