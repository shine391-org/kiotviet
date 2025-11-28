<?php

namespace Tests\Support\Fakes;

use App\Repositories\Webhooks\WebhookSubscriptionRepository;

/**
 * In-memory fake for webhook subscriptions.
 * @agent-fake: WebhookSubscriptionRepository
 */
class FakeWebhookSubscriptionRepository extends WebhookSubscriptionRepository
{
    private array $rows = [];
    private int $id = 1;

    public function __construct()
    {
        // Bỏ qua parent để không tạo kết nối DB
    }

    public function isReady(): bool
    {
        return true;
    }

    public function create(array $data): array
    {
        $row = $data + [
            'id' => $this->id++,
            'is_active' => $data['is_active'] ?? 1,
            'secret' => $data['secret'] ?? null,
            'created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
        ];
        $this->rows[] = $row;
        return $row;
    }

    public function activeForEvent(string $event): array
    {
        return array_values(array_filter($this->rows, fn ($r) =>
            ($r['event'] ?? '') === $event && ($r['is_active'] ?? 1)
        ));
    }

    public function findAll(array $filters = []): array
    {
        return $this->rows;
    }

    public function all(): array
    {
        return $this->rows;
    }

    public function reset(): void
    {
        $this->rows = [];
        $this->id = 1;
    }
}
