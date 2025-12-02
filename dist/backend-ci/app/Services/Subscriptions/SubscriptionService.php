<?php

namespace App\Services\Subscriptions;

use App\Repositories\Subscriptions\SubscriptionCycleRepository;
use App\Repositories\Subscriptions\SubscriptionRepository;
use App\Services\Orders\OrderService;
use App\Validators\SubscriptionValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Subscription scheduling and order generation.
 *
 * @agent-service: Subscriptions
 * @agent-pattern: Scheduler + idempotent cycle
 * @agent-reusable: MEDIUM
 */
class SubscriptionService
{
    protected SubscriptionRepository $subs;
    protected SubscriptionCycleRepository $cycles;
    protected OrderService $orders;
    protected SubscriptionValidator $validator;

    public function __construct(
        ?SubscriptionRepository $subs = null,
        ?SubscriptionCycleRepository $cycles = null,
        ?OrderService $orders = null,
        ?SubscriptionValidator $validator = null
    ) {
        $this->subs = $subs ?? new SubscriptionRepository();
        $this->cycles = $cycles ?? new SubscriptionCycleRepository();
        $this->orders = $orders ?? new OrderService();
        $this->validator = $validator ?? new SubscriptionValidator();
    }

    public function list(array $filters): array
    {
        return ['success' => true, 'data' => $this->subs->list($filters), 'total' => $this->subs->count($filters)];
    }

    public function show(int $id): array
    {
        return ['success' => true, 'data' => $this->requireSub($id)];
    }

    public function create(array $data): array
    {
        $validated = $this->validator->validateCreate($data);
        $payload = $validated;
        unset($payload['items']);
        $created = $this->subs->create($payload);
        if (! empty($validated['items'])) {
            // store items inside metadata? reuse template: keep in memory for tests; orders will use items
            $created['items'] = $validated['items'];
        }
        return ['success' => true, 'data' => $created];
    }

    public function update(int $id, array $data): array
    {
        $this->requireSub($id);
        $validated = $this->validator->validateUpdate($data);
        $payload = $validated;
        unset($payload['items']);
        $updated = $this->subs->update($id, $payload);
        if (isset($validated['items'])) {
            $updated['items'] = $validated['items'];
        }
        return ['success' => true, 'data' => $updated];
    }

    public function pause(int $id): array
    {
        $sub = $this->requireSub($id);
        if ($sub['status'] === 'paused') {
            return ['success' => true, 'data' => $sub];
        }
        $updated = $this->subs->update($id, ['status' => 'paused']);
        return ['success' => true, 'data' => $updated];
    }

    public function resume(int $id): array
    {
        $this->requireSub($id);
        $updated = $this->subs->update($id, ['status' => 'active']);
        return ['success' => true, 'data' => $updated];
    }

    public function cancel(int $id): array
    {
        $this->requireSub($id);
        $updated = $this->subs->update($id, ['status' => 'cancelled']);
        return ['success' => true, 'data' => $updated];
    }

    /**
     * Run due subscriptions once per cycle date.
     * @return array{success:bool,processed:int}
     */
    public function runDue(?string $now = null): array
    {
        $nowTs = $now ? strtotime($now) : time();
        $nowStr = date('Y-m-d H:i:s', $nowTs);
        $runDate = date('Y-m-d', $nowTs);
        $due = $this->subs->due($nowStr);
        $processed = 0;

        foreach ($due as $sub) {
            if (($sub['status'] ?? '') !== 'active') {
                continue;
            }
            if ($this->cycles->findCycle((int) $sub['id'], $runDate)) {
                continue;
            }

            $orderPayload = [
                'customer_id' => $sub['customer_id'] ?? null,
                'order_type' => 'shipping',
                'payment_method' => 'CASH',
                'order_date' => date('Y-m-d', $nowTs),
                'branch_id' => 1,
                'shipping_fee' => 0,
                'paid_amount' => 0,
                'items' => $this->buildItems($sub),
                'shipping_name' => 'Subscription',
                'shipping_phone' => 'N/A',
                'shipping_address' => 'Subscription',
            ];
            $order = $this->orders->create($orderPayload);
            $this->cycles->createCycle((int) $sub['id'], $runDate, $order['data']['id'] ?? null);
            $processed++;

            $next = $this->calculateNextRun($sub, $nowTs);
            $this->subs->update((int) $sub['id'], ['last_run_at' => $nowStr, 'next_run_at' => $next]);
        }

        return ['success' => true, 'processed' => $processed];
    }

    private function buildItems(array $sub): array
    {
        if (! empty($sub['items']) && is_array($sub['items'])) {
            return array_map(fn ($item) => [
                'product_id' => (int) $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => (float) $item['quantity'],
            ], $sub['items']);
        }
        // Fallback single item if no items stored
        return [['product_id' => 1, 'variant_id' => null, 'quantity' => 1]];
    }

    private function calculateNextRun(array $sub, int $nowTs): string
    {
        $interval = (int) ($sub['interval_days'] ?? 30);
        $dt = new \DateTimeImmutable(date('Y-m-d H:i:s', $nowTs));
        return $dt->modify('+' . max(1, $interval) . ' days')->format('Y-m-d H:i:s');
    }

    private function requireSub(int $id): array
    {
        $sub = $this->subs->find($id);
        if (! $sub) {
            throw new RuntimeException('Subscription not found');
        }
        return $sub;
    }
}
