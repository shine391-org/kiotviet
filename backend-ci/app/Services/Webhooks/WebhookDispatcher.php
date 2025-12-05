<?php

namespace App\Services\Webhooks;

use App\Repositories\Webhooks\WebhookEventRepository;
use App\Repositories\Webhooks\WebhookSubscriptionRepository;
use RuntimeException;

/**
 * Dispatch domain events to configured webhook endpoints.
 *
 * @agent-service: Webhook dispatcher
 * @agent-pattern: Event -> queue -> HTTP
 * @agent-reusable: MEDIUM
 */
class WebhookDispatcher
{
    protected WebhookSubscriptionRepository $subscriptions;
    protected WebhookEventRepository $events;
    /** @var callable|null */
    protected $sender;
    protected int $timeout;
    protected string $userAgent = 'LanoCRM-Webhook/1.0';

    public function __construct(
        ?WebhookSubscriptionRepository $subscriptions = null,
        ?WebhookEventRepository $events = null,
        ?callable $sender = null,
        int $timeoutSeconds = 5
    ) {
        $this->subscriptions = $subscriptions ?? new WebhookSubscriptionRepository();
        $this->events = $events ?? new WebhookEventRepository();
        $this->sender = $sender;
        $this->timeout = $timeoutSeconds;
    }

    /**
     * Dispatch event to all active subscriptions.
     *
     * @agent-use: Internal call from services
     */
    public function dispatch(string $event, array $data): array
    {
        if (! $this->ready()) {
            return ['success' => true, 'sent' => 0, 'skipped' => true];
        }

        $subs = $this->subscriptions->activeForEvent($event);
        if (empty($subs)) {
            return ['success' => true, 'sent' => 0, 'skipped' => true];
        }

        $basePayload = [
            'event' => $event,
            'timestamp' => date('c'),
            'data' => $data,
        ];

        $sent = 0; $failed = 0;
        foreach ($subs as $sub) {
            $body = $basePayload + ['subscription_id' => $sub['id']];
            $signature = $sub['secret'] ? $this->sign($body, $sub['secret']) : null;
            $record = $this->events->create([
                'event' => $event,
                'payload' => [
                    'subscription_id' => $sub['id'],
                    'target_url' => $sub['target_url'],
                    'body' => $body,
                    'signature' => $signature,
                ],
                'status' => 'pending',
                'attempts' => 0,
            ]);

            $result = $this->deliver($record);
            $sent += $result['status'] === 'sent' ? 1 : 0;
            $failed += $result['status'] === 'failed' ? 1 : 0;
        }

        return ['success' => true, 'sent' => $sent, 'failed' => $failed, 'total' => $sent + $failed];
    }

    /** Retry single queued event. @agent-use: POST /api/webhook-events/{id}/retry */
    public function retry(int $id): array
    {
        if (! $this->ready()) {
            throw new RuntimeException('Webhook tables not migrated');
        }
        $event = $this->events->findById($id);
        if (! $event) {
            throw new RuntimeException('Webhook event not found');
        }
        $result = $this->deliver($event);
        return ['success' => true, 'data' => $this->events->findById($id), 'result' => $result['status']];
    }

    /** List stored events. @agent-use: GET /api/webhook-events */
    public function listEvents(array $filters): array
    {
        if (! $this->ready()) {
            return ['success' => true, 'data' => [], 'pagination' => ['page' => 1, 'limit' => 20, 'total' => 0, 'total_pages' => 0]];
        }
        $page = (int) ($filters['page'] ?? 1);
        $limit = (int) ($filters['limit'] ?? 20);
        $eventName = isset($filters['event']) ? strtolower(trim((string) $filters['event'])) : null;
        $status = isset($filters['status']) ? strtolower(trim((string) $filters['status'])) : null;

        $filters = ['page' => $page, 'limit' => $limit];
        if ($eventName) { $filters['event'] = $eventName; }
        if ($status) { $filters['status'] = $status; }

        $rows = $this->events->findAll($filters);
        $total = $this->events->count($filters);

        return [
            'success' => true,
            'data' => $rows,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => (int) ceil($total / ($limit ?: 1)),
            ],
        ];
    }

    private function ready(): bool
    {
        return $this->subscriptions->isReady() && $this->events->isReady();
    }

    private function deliver(array $eventRow): array
    {
        $payload = $eventRow['payload'] ?? [];
        $url = $payload['target_url'] ?? null;
        $body = $payload['body'] ?? [];
        $signature = $payload['signature'] ?? null;
        if (! $url) {
            $this->events->markFailed($eventRow['id'], 'Missing target_url');
            return ['status' => 'failed'];
        }

        $headers = ['Content-Type' => 'application/json', 'User-Agent' => $this->userAgent];
        if ($signature) {
            $headers['X-Lano-Signature'] = $signature;
        }

        $lastError = null;
        $attempts = 0;
        try {
            $sender = $this->sender ?? [$this, 'defaultSend'];
            for ($i = 0; $i < 3; $i++) {
                $attempts++;
                $resp = $sender($url, $headers, $body, $this->timeout);
                $ok = $resp['success'] ?? false;
                if ($ok) {
                    $this->events->markSent($eventRow['id'], $attempts);
                    return ['status' => 'sent'];
                }
                $lastError = $resp['error'] ?? ('HTTP ' . ($resp['status_code'] ?? '500'));
                usleep(100000); // 100ms backoff
            }
            $this->events->markFailed($eventRow['id'], $lastError ?? 'Webhook failed', $attempts);
            return ['status' => 'failed', 'error' => $lastError];
        } catch (\Throwable $e) {
            $attempts = max(1, $attempts);
            $this->events->markFailed($eventRow['id'], $e->getMessage(), $attempts);
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    private function sign(array $body, string $secret): string
    {
        return hash_hmac('sha256', json_encode($body), $secret);
    }

    /** Default HTTP sender using curlrequest. */
    public function defaultSend(string $url, array $headers, array $body, int $timeout): array
    {
        $client = \Config\Services::curlrequest([
            'timeout' => $timeout,
            'http_errors' => false,
        ]);
        $response = $client->post($url, [
            'headers' => $headers,
            'json' => $body,
        ]);
        $status = $response->getStatusCode();
        return [
            'success' => $status >= 200 && $status < 300,
            'status_code' => $status,
            'error' => $response->getReason(),
        ];
    }
}
