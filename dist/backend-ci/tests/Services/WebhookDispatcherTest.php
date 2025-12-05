<?php

namespace Tests\Services;

use App\Services\Webhooks\WebhookDispatcher;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Fakes\FakeWebhookSubscriptionRepository;
use Tests\Support\Fakes\FakeWebhookEventRepository;

/**
 * @agent-test: WebhookDispatcher
 * @agent-pattern: In-memory fakes
 */
class WebhookDispatcherTest extends CIUnitTestCase
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
    public function it_dispatches_and_marks_sent()
    {
        $this->subs->create([
            'event' => 'order.completed',
            'target_url' => 'https://example.com/hook',
            'secret' => 'secret-key',
            'is_active' => 1,
        ]);

        $requests = [];
        $sender = function (string $url, array $headers, array $body, int $timeout = 5) use (&$requests) {
            $requests[] = compact('url', 'headers', 'body');
            return ['success' => true, 'status_code' => 200];
        };

        $dispatcher = new WebhookDispatcher($this->subs, $this->events, $sender);

        $result = $dispatcher->dispatch('order.completed', ['order_id' => 10]);
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['sent']);

        $events = $this->events->findAll(['event' => 'order.completed', 'limit' => 10, 'page' => 1]);
        $this->assertCount(1, $events);
        $this->assertEquals('sent', $events[0]['status']);
        $this->assertEquals(1, $events[0]['attempts']);
        $this->assertNotEmpty($requests);
        $this->assertArrayHasKey('X-Lano-Signature', $requests[0]['headers']);
    }

    /** @test */
    public function it_marks_failed_when_sender_errors()
    {
        $this->subs->create([
            'event' => 'invoice.generated',
            'target_url' => 'https://hooks.test/invoice',
            'is_active' => 1,
        ]);

        $sender = function (string $url, array $headers, array $body, int $timeout = 5) {
            return ['success' => false, 'status_code' => 500, 'error' => 'Server error'];
        };
        $dispatcher = new WebhookDispatcher($this->subs, $this->events, $sender);

        $dispatcher->dispatch('invoice.generated', ['invoice_id' => 99]);

        $events = $this->events->findAll(['event' => 'invoice.generated', 'limit' => 5, 'page' => 1]);
        $this->assertNotEmpty($events);
        $this->assertEquals('failed', $events[0]['status']);
        $this->assertGreaterThanOrEqual(1, $events[0]['attempts']);
        $this->assertNotEmpty($events[0]['last_error']);
    }
}
