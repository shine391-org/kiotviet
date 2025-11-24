<?php

namespace App\Services\Webhooks;

use App\Repositories\Webhooks\WebhookSubscriptionRepository;
use App\Validators\WebhookSubscriptionValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Manage webhook subscriptions (CRUD).
 *
 * @agent-service: Webhook subscriptions
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class WebhookSubscriptionService
{
    protected WebhookSubscriptionRepository $repo;
    protected WebhookSubscriptionValidator $validator;

    public function __construct(
        ?WebhookSubscriptionRepository $repo = null,
        ?WebhookSubscriptionValidator $validator = null
    ) {
        $this->repo = $repo ?? new WebhookSubscriptionRepository();
        $this->validator = $validator ?? new WebhookSubscriptionValidator();
    }

    /** List subscriptions. @agent-use: GET /api/webhooks/subscriptions */
    public function list(array $filters): array
    {
        $validated = $this->validator->validateList($filters);
        $rows = $this->repo->isReady() ? $this->repo->findAll($validated) : [];
        $total = $this->repo->isReady() ? $this->repo->count($validated) : 0;

        return [
            'success' => true,
            'data' => $rows,
            'pagination' => [
                'page' => $validated['page'],
                'limit' => $validated['limit'],
                'total' => $total,
                'total_pages' => (int) ceil($total / ($validated['limit'] ?: 1)),
            ],
        ];
    }

    /** Create subscription. @agent-use: POST /api/webhooks/subscriptions */
    public function create(array $data): array
    {
        $payload = $this->validator->validateCreate($data);
        $this->assertReady();

        $created = $this->repo->create($payload);
        return ['success' => true, 'data' => $created];
    }

    /** Update subscription. @agent-use: PUT /api/webhooks/subscriptions/{id} */
    public function update(int $id, array $data): array
    {
        $payload = $this->validator->validateUpdate($data);
        $this->assertReady();

        $existing = $this->repo->findById($id);
        if (! $existing) {
            throw new RuntimeException('Subscription not found');
        }
        $this->repo->update($id, $payload);
        return ['success' => true, 'data' => $this->repo->findById($id)];
    }

    public function activate(int $id): array
    {
        return $this->toggle($id, true);
    }

    public function deactivate(int $id): array
    {
        return $this->toggle($id, false);
    }

    private function toggle(int $id, bool $isActive): array
    {
        $this->assertReady();
        $existing = $this->repo->findById($id);
        if (! $existing) {
            throw new RuntimeException('Subscription not found');
        }
        $isActive ? $this->repo->activate($id) : $this->repo->deactivate($id);
        return ['success' => true, 'data' => $this->repo->findById($id)];
    }

    private function assertReady(): void
    {
        if (! $this->repo->isReady()) {
            throw new InvalidArgumentException('Webhook tables not migrated yet');
        }
    }
}
