<?php

namespace Tests\Services;

use App\Repositories\Webhooks\WebhookSubscriptionRepository;
use App\Services\Webhooks\WebhookSubscriptionService;
use App\Validators\WebhookSubscriptionValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\WebhookSchemaTrait;

/**
 * @agent-test: WebhookSubscriptionService unified MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait + WebhookSchemaTrait (MySQL-only)
 */
class WebhookSubscriptionServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use WebhookSchemaTrait;

    private WebhookSubscriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        
        // Use WebhookSchemaTrait for comprehensive schema
        $this->resetWebhookSchema();

        // Pass database connection to repository so it uses correct tables
        $repo = new WebhookSubscriptionRepository(null, $this->db);
        $this->service = new WebhookSubscriptionService($repo, new WebhookSubscriptionValidator());
    }
    
    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
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

        // Debug: Check if data was actually inserted
        $directQuery = $this->db->table('db_webhook_subscriptions')->get();
        $directResult = $directQuery ? $directQuery->getResultArray() : [];
        echo "Direct query result: " . json_encode($directResult) . "\n";

        $list = $this->service->list(['event' => 'order.created']);
        echo "List result: " . json_encode($list) . "\n";
        
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
