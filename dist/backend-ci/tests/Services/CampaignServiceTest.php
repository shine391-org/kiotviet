<?php

namespace Tests\Services;

use App\Services\Campaigns\CampaignService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: CampaignService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class CampaignServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private CampaignService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->service = new CampaignService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_campaign_and_adds_member()
    {
        $campaign = $this->service->create(['name' => 'Summer', 'budget' => 1000]);
        $this->assertTrue($campaign['success']);

        $res = $this->service->addMember($campaign['data']['id'], ['lead_id' => 1]);
        $this->assertTrue($res['success']);
    }
}
