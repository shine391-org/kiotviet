<?php

namespace Tests\Services;

use App\Repositories\Webhooks\WebhookEventRepository;
use App\Repositories\Webhooks\WebhookSubscriptionRepository;
use App\Services\Webhooks\WebhookDispatcher;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\Database\WebhookSchemaTrait;

/** @agent-test: Webhook event flows @agent-pattern: Dispatcher end-to-end with stub transport */
class WebhookEventFlowTest extends CIUnitTestCase
{
    use WebhookSchemaTrait;

    protected $db;
    private WebhookSubscriptionRepository $subs;
    private WebhookEventRepository $events;

    protected function setUp(): void
    {
        parent::setUp();

        $config = config('Database');
        if (extension_loaded('sqlite3')) {
            $config->tests = [
                'DBDriver'    => 'SQLite3',
                'database'    => ':memory:',
                'DBPrefix'    => 'db_',
                'foreignKeys' => true,
                'DBDebug'     => true,
            ];
        } else {
            $config->tests = [
                'hostname' => '127.0.0.1',
                'port' => 3307,
                'username' => 'lanocrm_user',
                'password' => 'KP7n4RjcDbedSE2W8GgA',
                'database' => 'lanocrm_test',
                'DBDriver' => 'MySQLi',
                'DBPrefix' => 'db_',
                'charset' => 'utf8mb4',
                'DBCollat' => 'utf8mb4_general_ci',
                'DBDebug' => true,
            ];
        }
        $config->defaultGroup = 'tests';

        try {
            $this->db = Database::connect('tests', false);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database not available for webhook tests: ' . $e->getMessage());
        }

        $this->resetWebhookSchema();
        $this->subs = new WebhookSubscriptionRepository(null, $this->db);
        $this->events = new WebhookEventRepository(null, $this->db);
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
        ]);

        $calls = 0;
        $sender = function () use (&$calls) {
            $calls++;
            return $calls >= 3
                ? ['success' => true, 'status_code' => 200]
                : ['success' => false, 'status_code' => 500, 'error' => 'fail'];
        };

        $dispatcher = new WebhookDispatcher($this->subs, $this->events, $sender);
        $dispatcher->dispatch('order.created', ['order_id' => 9]);

        $events = $this->events->findAll(['limit' => 1, 'page' => 1]);
        $this->assertEquals(3, $events[0]['attempts']);
        $this->assertEquals('sent', $events[0]['status']);
    }

    /** @test */
    public function it_logs_webhook_failures()
    {
        $this->subs->create([
            'event' => 'invoice.generated',
            'target_url' => 'https://hooks.test/fail',
        ]);

        $dispatcher = new WebhookDispatcher(
            $this->subs,
            $this->events,
            fn () => ['success' => false, 'status_code' => 500, 'error' => 'server down']
        );
        $dispatcher->dispatch('invoice.generated', ['invoice_id' => 77]);

        $events = $this->events->findAll(['limit' => 1, 'page' => 1]);
        $this->assertEquals('failed', $events[0]['status']);
        $this->assertEquals(3, $events[0]['attempts']);
        $this->assertStringContainsString('server down', (string) $events[0]['last_error']);
    }

    /** @test */
    public function webhook_payload_format_is_correct()
    {
        $this->subs->create([
            'event' => 'order.completed',
            'target_url' => 'https://hooks.test/payload',
            'secret' => 'secret123',
        ]);

        $requests = [];
        $sender = function (string $url, array $headers, array $body) use (&$requests) {
            $requests[] = ['headers' => $headers, 'body' => $body];
            return ['success' => true, 'status_code' => 200];
        };

        $dispatcher = new WebhookDispatcher($this->subs, $this->events, $sender);
        $dispatcher->dispatch('order.completed', ['order_id' => 501, 'total' => 1000]);

        $this->assertNotEmpty($requests);
        $body = $requests[0]['body'];
        $this->assertEquals('order.completed', $body['event']);
        $this->assertArrayHasKey('timestamp', $body);
        $this->assertEquals(501, $body['data']['order_id']);
        $this->assertArrayHasKey('X-Lano-Signature', $requests[0]['headers']);
    }
}
