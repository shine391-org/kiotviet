<?php

namespace Tests\Services;

use App\Repositories\Webhooks\WebhookEventRepository;
use App\Repositories\Webhooks\WebhookSubscriptionRepository;
use App\Services\Webhooks\WebhookDispatcher;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\WebhookSchemaTrait;

/**
 * @agent-test: WebhookDispatcher
 * @agent-pattern: MySQL-only test with DevDatabaseTrait
 */
class WebhookDispatcherTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use WebhookSchemaTrait;

    private WebhookSubscriptionRepository $subs;
    private WebhookEventRepository $events;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetWebhookSchema();
        
        // Create database connection without prefix for webhook tables
        $dbWithoutPrefix = \Config\Database::connect('tests');
        $dbWithoutPrefix->setPrefix('');
        
        $this->subs = new WebhookSubscriptionRepository(null, $dbWithoutPrefix);
        $this->events = new WebhookEventRepository(null, $dbWithoutPrefix);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_dispatches_and_marks_sent()
    {
        $this->subs->create([
            'event' => 'order.completed',
            'target_url' => 'https://example.com/hook',
            'secret' => 'secret-key',
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
        ]);

        $sender = function (string $url, array $headers, array $body, int $timeout = 5) {
            return ['success' => false, 'status_code' => 500, 'error' => 'Server error'];
        };
        $dispatcher = new WebhookDispatcher($this->subs, $this->events, $sender);

        $dispatcher->dispatch('invoice.generated', ['invoice_id' => 99]);

        $events = $this->events->findAll(['event' => 'invoice.generated', 'limit' => 5, 'page' => 1]);
        $this->assertEquals('failed', $events[0]['status']);
        $this->assertGreaterThanOrEqual(1, $events[0]['attempts']); // retries allowed
        $this->assertNotEmpty($events[0]['last_error']);
    }
}
