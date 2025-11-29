<?php

namespace Tests\Services;

use App\Services\Campaigns\EmailCampaignService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: EmailCampaignService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class EmailCampaignServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private EmailCampaignService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->service = new EmailCampaignService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_schedules_and_updates_status()
    {
        $campaign = $this->service->schedule([
            'subject' => 'Hello',
            'template' => 'Hi there',
            'status' => 'scheduled',
        ]);
        $this->assertTrue($campaign['success']);

        $updated = $this->service->updateStatus($campaign['data']['id'], 'paused');
        $this->assertEquals('paused', $updated['data']['status']);
    }
}
