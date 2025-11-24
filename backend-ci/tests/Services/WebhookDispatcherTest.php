<?php

namespace Tests\Services;

use App\Repositories\Webhooks\WebhookEventRepository;
use App\Repositories\Webhooks\WebhookSubscriptionRepository;
use App\Services\Webhooks\WebhookDispatcher;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\Database\WebhookSchemaTrait;

/** @agent-test: WebhookDispatcher @agent-pattern: Dispatch with stub transport */
class WebhookDispatcherTest extends CIUnitTestCase
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
                'DSN'       => '',
                'hostname'  => '127.0.0.1',
                'port'      => 3307,
                'username'  => 'lanocrm_user',
                'password'  => 'KP7n4RjcDbedSE2W8GgA',
                'database'  => 'lanocrm_test',
                'DBDriver'  => 'MySQLi',
                'DBPrefix'  => 'db_',
                'pConnect'  => false,
                'DBDebug'   => true,
                'charset'   => 'utf8mb4',
                'DBCollat'  => 'utf8mb4_general_ci',
            ];
        }
        $config->defaultGroup = 'tests';

        try {
            $this->db = Database::connect('tests', false);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database connection not available for webhook tests: ' . $e->getMessage());
        }

        $this->resetWebhookSchema();
        $this->subs = new WebhookSubscriptionRepository(null, $this->db);
        $this->events = new WebhookEventRepository(null, $this->db);
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
