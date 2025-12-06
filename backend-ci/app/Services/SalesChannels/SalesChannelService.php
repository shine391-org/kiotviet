<?php

namespace App\Services\SalesChannels;

use App\Repositories\SalesChannels\SalesChannelRepository;
use InvalidArgumentException;
use RuntimeException;

class SalesChannelService
{
    protected SalesChannelRepository $repo;

    public function __construct(?SalesChannelRepository $repo = null)
    {
        $this->repo = $repo ?? new SalesChannelRepository();
    }

    public function list(array $filters): array
    {
        $rows = $this->repo->findAll($filters);
        return [
            'success' => true,
            'data' => array_map([$this, 'transform'], $rows),
        ];
    }

    public function get(int $id): array
    {
        $channel = $this->repo->findById($id);
        if (!$channel) {
            throw new RuntimeException('Sales channel not found');
        }
        return ['success' => true, 'data' => $this->transform($channel)];
    }

    public function create(array $data): array
    {
        if (empty($data['code']) || empty($data['name'])) {
            throw new InvalidArgumentException('Code and name are required');
        }

        if ($this->repo->findByCode($data['code'])) {
            throw new InvalidArgumentException('Sales channel code already exists');
        }

        $channel = $this->repo->create($this->sanitize($data));
        return ['success' => true, 'data' => $this->transform($channel), 'message' => 'Sales channel created'];
    }

    public function update(int $id, array $data): array
    {
        if (!$this->repo->findById($id)) {
            throw new RuntimeException('Sales channel not found');
        }

        $channel = $this->repo->update($id, $this->sanitize($data));
        return ['success' => true, 'data' => $this->transform($channel), 'message' => 'Sales channel updated'];
    }

    public function delete(int $id): array
    {
        if (!$this->repo->findById($id)) {
            throw new RuntimeException('Sales channel not found');
        }
        $this->repo->delete($id);
        return ['success' => true, 'message' => 'Sales channel deleted'];
    }

    private function sanitize(array $data): array
    {
        $allowed = ['code', 'name', 'description', 'icon', 'color', 'is_active', 'is_default', 'sort_order', 'settings'];
        $result = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $result[$key] = $key === 'settings' ? json_encode($data[$key]) : $data[$key];
            }
        }
        return $result;
    }

    private function transform(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'code' => $row['code'],
            'name' => $row['name'],
            'description' => $row['description'] ?? null,
            'icon' => $row['icon'] ?? null,
            'color' => $row['color'] ?? null,
            'is_active' => (bool) ($row['is_active'] ?? true),
            'is_default' => (bool) ($row['is_default'] ?? false),
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'settings' => is_string($row['settings'] ?? null) ? json_decode($row['settings'], true) : ($row['settings'] ?? null),
        ];
    }
}
