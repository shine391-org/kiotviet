<?php

namespace Tests\Services;

use App\Repositories\Webhooks\WebhookSubscriptionRepository;
use App\Services\Webhooks\WebhookSubscriptionService;
use App\Validators\WebhookSubscriptionValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\Database\WebhookSchemaTrait;

/** @agent-test: WebhookSubscriptionService @agent-pattern: Service test with sqlite fallback */
class WebhookSubscriptionServiceTest extends CIUnitTestCase
{
    use WebhookSchemaTrait;

    protected $db;
    private WebhookSubscriptionService $service;

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

        $repo = new WebhookSubscriptionRepository(null, $this->db);
        $this->service = new WebhookSubscriptionService($repo, new WebhookSubscriptionValidator());
    }

    /** @test */
    public function it_creates_and_lists_subscriptions()
    {
        $created = $this->service->create([
            'event' => 'order.created',
            'target_url' => 'https://example.com/webhook',
            'secret' => 'abc',
        ]);

        $this->assertTrue($created['success']);
        $this->assertEquals('order.created', $created['data']['event']);
        $this->assertTrue($created['data']['is_active']);

        $list = $this->service->list(['event' => 'order.created']);
        $this->assertCount(1, $list['data']);
        $this->assertEquals('https://example.com/webhook', $list['data'][0]['target_url']);
    }

    /** @test */
    public function it_activates_and_deactivates_subscription()
    {
        $created = $this->service->create([
            'event' => 'return.approved',
            'target_url' => 'https://hook.test/return',
            'is_active' => false,
        ]);

        $this->assertFalse($created['data']['is_active']);

        $activated = $this->service->activate($created['data']['id']);
        $this->assertTrue($activated['data']['is_active']);

        $deactivated = $this->service->deactivate($created['data']['id']);
        $this->assertFalse($deactivated['data']['is_active']);
    }
}
