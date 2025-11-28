<?php

namespace Tests\Services;

use App\Services\Webhooks\WebhookDispatcher;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Fakes\FakeWebhookSubscriptionRepository;
use Tests\Support\Fakes\FakeWebhookEventRepository;

/**
 * @agent-test: Webhook event flows
 * @agent-pattern: In-memory fakes
 */
class WebhookEventFlowTest extends CIUnitTestCase
{
    private FakeWebhookSubscriptionRepository $subs;
    private FakeWebhookEventRepository $events;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subs = new FakeWebhookSubscriptionRepository();
        $this->events = new FakeWebhookEventRepository();
    }

    /** @test */
    public function order_lifecycle_fires_all_events()
    {
        $eventNames = [
            'order.created', 'order.confirmed', 'order.processing',
            'order.shipping', 'order.delivered', 'order.completed',
        ];
        foreach ($eventNames as $name) {
            $this->subs->create([
                'event' => $name,
                'target_url' => 'https://hooks.test/' . $name,
                'is_active' => 1,
            ]);
        }

        $requests = [];
        $sender = function (string $url, array $headers, array $body, int $timeout = 5) use (&$requests) {
            $requests[] = ['url' => $url, 'body' => $body];
            return ['success' => true, 'status_code' => 200];
        };
        $dispatcher = new WebhookDispatcher($this->subs, $this->events, $sender);

        foreach ($eventNames as $name) {
            $dispatcher->dispatch($name, ['order_id' => 123]);
        }

        $rows = $this->events->findAll(['limit' => 20, 'page' => 1]);
        $this->assertCount(count($eventNames), $rows);
        $this->assertEquals(count($eventNames), count($requests));
        foreach ($rows as $row) {
            $this->assertEquals('sent', $row['status']);
            $this->assertEquals(1, $row['attempts']);
            $this->assertArrayHasKey('payload', $row);
        }
    }

    /** @test */
    public function return_lifecycle_fires_all_events()
    {
        $eventNames = ['return.requested', 'return.approved', 'return.completed'];
        foreach ($eventNames as $name) {
            $this->subs->create([
                'event' => $name,
                'target_url' => 'https://hooks.test/' . $name,
                'is_active' => 1,
            ]);
        }

        $dispatcher = new WebhookDispatcher(
            $this->subs,
            $this->events,
            fn () => ['success' => true, 'status_code' => 200]
        );

        foreach ($eventNames as $name) {
            $dispatcher->dispatch($name, ['return_id' => 55]);
        }

        $rows = $this->events->findAll(['limit' => 10, 'page' => 1]);
        $this->assertCount(3, $rows);
        $this->assertTrue(array_reduce($rows, fn ($c, $r) => $c && $r['status'] === 'sent', true));
    }

    /** @test */
    public function it_retries_failed_webhooks_and_counts_attempts()
    {
        $this->subs->create([
            'event' => 'order.created',
            'target_url' => 'https://hooks.test/retry',
            'is_active' => 1,
        ]);

        $calls = 0;
        $sender = function () use (&$calls) {
            $calls++;
            return $calls >= 3
                ? ['success' => true, 'status_code' => 200]
                : ['success' => false, 'status_code' => 500, 'error' => 'fail'];
        };
        $dispatcher = new WebhookDispatcher($this->subs, $this->events, $sender);

        $dispatcher->dispatch('order.created', ['order_id' => 1]);

        $rows = $this->events->findAll(['event' => 'order.created', 'limit' => 5, 'page' => 1]);
        $this->assertCount(1, $rows);
        $this->assertEquals('sent', $rows[0]['status']);
        $this->assertGreaterThanOrEqual(3, $rows[0]['attempts']);
    }

    /** @test */
    public function it_logs_webhook_failures()
    {
        $this->subs->create([
            'event' => 'order.created',
            'target_url' => 'https://hooks.test/retry',
            'is_active' => 1,
        ]);

        $sender = function () {
            return ['success' => false, 'status_code' => 500, 'error' => 'down'];
        };
        $dispatcher = new WebhookDispatcher($this->subs, $this->events, $sender);

        $dispatcher->dispatch('order.created', ['order_id' => 1]);

        $rows = $this->events->findAll(['event' => 'order.created', 'limit' => 5, 'page' => 1]);
        $this->assertCount(1, $rows);
        $this->assertEquals('failed', $rows[0]['status']);
        $this->assertNotEmpty($rows[0]['last_error'] ?? null);
    }

    /** @test */
    public function webhook_payload_format_is_correct()
    {
        $this->subs->create([
            'event' => 'order.created',
            'target_url' => 'https://hooks.test/a',
            'is_active' => 1,
        ]);

        $dispatcher = new WebhookDispatcher(
            $this->subs,
            $this->events,
            fn ($url, $headers, $body) => ['success' => true, 'status_code' => 200]
        );

        $dispatcher->dispatch('order.created', ['order_id' => 42]);

        $rows = $this->events->findAll(['event' => 'order.created', 'limit' => 1, 'page' => 1]);
        $this->assertNotEmpty($rows);
        $payload = $rows[0]['payload'] ?? null;
        $this->assertIsArray($payload);
        $this->assertEquals('order.created', $payload['body']['event'] ?? null);
        $this->assertEquals(42, $payload['body']['data']['order_id'] ?? null);
    }
}
