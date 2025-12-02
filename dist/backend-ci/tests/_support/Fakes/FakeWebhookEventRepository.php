<?php

namespace Tests\Support\Fakes;

use App\Repositories\Webhooks\WebhookEventRepository;

/**
 * In-memory fake for webhook events.
 * @agent-fake: WebhookEventRepository
 */
class FakeWebhookEventRepository extends WebhookEventRepository
{
    private array $rows = [];
    private int $id = 1;

    public function __construct()
    {
        // Skip parent constructor to avoid DB
    }

    public function isReady(): bool
    {
        return true;
    }

    public function create(array $data): array
    {
        $row = $data + [
            'id' => $this->id++,
            'status' => $data['status'] ?? 'pending',
            'attempts' => $data['attempts'] ?? 0,
            'payload' => $data['payload'] ?? [],
            'created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at' => $data['updated_at'] ?? date('Y-m-d H:i:s'),
        ];
        $this->rows[] = $row;
        return $row;
    }

    public function findAll(array $filters): array
    {
        $rows = $this->rows;
        if (! empty($filters['event'])) {
            $rows = array_filter($rows, fn ($r) => ($r['event'] ?? null) === $filters['event']);
        }
        return array_values($rows);
    }

    public function count(array $filters): int
    {
        return count($this->findAll($filters));
    }

    public function all(): array
    {
        return $this->rows;
    }

    public function findById(int $id): ?array
    {
        foreach ($this->rows as $r) {
            if ($r['id'] === $id) return $r;
        }
        return null;
    }

    public function markSent(int $id, int $attempts = 1): bool
    {
        return $this->updateStatus($id, 'sent', null, $attempts);
    }

    public function markFailed(int $id, string $error, int $attempts = 1): bool
    {
        return $this->updateStatus($id, 'failed', $error, $attempts);
    }

    private function updateStatus(int $id, string $status, ?string $error, int $attempts): bool
    {
        foreach ($this->rows as &$r) {
            if ($r['id'] === $id) {
                $r['status'] = $status;
                $r['last_error'] = $error;
                $r['attempts'] = ($r['attempts'] ?? 0) + $attempts;
                $r['updated_at'] = date('Y-m-d H:i:s');
                return true;
            }
        }
        return false;
    }

    public function reset(): void
    {
        $this->rows = [];
        $this->id = 1;
    }
}
