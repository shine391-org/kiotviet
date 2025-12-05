<?php

namespace App\Services\Orders;

use App\Repositories\Orders\OrderSubscriptionRepository;
use App\Validators\OrderSubscriptionValidator;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

/**
 * Order subscription scheduler.
 *
 * @agent-service: Order subscriptions
 * @agent-pattern: Scheduler + apply template
 * @agent-reusable: MEDIUM
 */
class OrderSubscriptionService
{
    protected OrderSubscriptionRepository $repo;
    protected OrderSubscriptionValidator $validator;
    protected OrderTemplateService $templates;

    public function __construct(
        ?OrderSubscriptionRepository $repo = null,
        ?OrderSubscriptionValidator $validator = null,
        ?OrderTemplateService $templates = null
    ) {
        $this->repo = $repo ?? new OrderSubscriptionRepository();
        $this->validator = $validator ?? new OrderSubscriptionValidator();
        $this->templates = $templates ?? new OrderTemplateService();
    }

    /** List subscriptions. */
    public function list(array $filters): array
    {
        return ['success' => true, 'data' => $this->repo->list($filters)];
    }

    /** Fetch subscription. */
    public function getSubscription(int $id): array
    {
        $sub = $this->repo->find($id);
        if (! $sub) {
            throw new RuntimeException('Subscription not found');
        }
        return $sub;
    }

    /** Create subscription. */
    public function createSubscription(array $data): array
    {
        $validated = $this->validator->validateCreate($data);
        $this->assertTemplateActive($validated['template_id']);
        $sub = $this->repo->create($validated);
        return ['success' => true, 'data' => $sub];
    }

    /** Update subscription. */
    public function updateSubscription(int $id, array $data): array
    {
        $validated = $this->validator->validateUpdate($data);
        $this->getSubscription($id);
        $sub = $this->repo->update($id, $validated);
        return ['success' => true, 'data' => $sub];
    }

    /**
     * Run due subscriptions and instantiate orders.
     *
     * @return array{success:bool,processed:int}
     */
    public function runDueSubscriptions(?string $now = null): array
    {
        $nowTs = $now ? strtotime($now) : time();
        $nowStr = date('Y-m-d H:i:s', $nowTs);
        $due = $this->repo->due($nowStr);
        $processed = 0;

        foreach ($due as $sub) {
            try {
                $this->assertTemplateActive((int) $sub['template_id']);
            } catch (InvalidArgumentException $e) {
                $this->repo->update((int) $sub['id'], ['status' => 'paused', 'last_run_at' => $nowStr]);
                continue;
            }

            try {
                $this->templates->applyTemplate((int) $sub['template_id'], [
                    'branch_id' => (int) $sub['branch_id'],
                    'payment_method' => $sub['payment_method'],
                    'order_type' => $sub['order_type'] ?? 'shipping',
                    'order_date' => date('Y-m-d', $nowTs),
                ]);
                $processed++;
            } catch (\Throwable $e) {
                // Skip failing subscription without blocking the rest
            }

            $nextRun = $this->addDays($nowStr, (int) ($sub['frequency_interval'] ?? 7));
            $this->repo->update((int) $sub['id'], [
                'last_run_at' => $nowStr,
                'next_run_at' => $nextRun,
            ]);
        }

        return ['success' => true, 'processed' => $processed];
    }

    private function assertTemplateActive(int $templateId): void
    {
        $template = $this->templates->show($templateId)['data'] ?? null;
        if (! $template || empty($template['is_active'])) {
            throw new InvalidArgumentException('Template inactive or missing');
        }
    }

    private function addDays(string $dateTime, int $days): string
    {
        $dt = new DateTimeImmutable($dateTime);
        return $dt->add(new DateInterval('P' . max(1, $days) . 'D'))->format('Y-m-d H:i:s');
    }
}
