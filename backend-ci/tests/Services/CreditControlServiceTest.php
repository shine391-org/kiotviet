<?php

namespace Tests\Services;

use App\Services\Accounting\CreditControlService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: CreditControlService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class CreditControlServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private CreditControlService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->service = new CreditControlService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_blocks_over_limit()
    {
        $this->service->upsertLimit(['customer_id' => 1, 'limit_amount' => 500, 'on_hold' => false]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->assertWithinLimit(1, 600, false);
    }

    /** @test */
    public function it_allows_override_and_updates_limit()
    {
        $this->service->upsertLimit(['customer_id' => 2, 'limit_amount' => 300, 'on_hold' => true]);
        $this->service->assertWithinLimit(2, 200, true); // override bypasses hold
        $limit = $this->service->upsertLimit(['customer_id' => 2, 'limit_amount' => 400, 'on_hold' => false])['data'];
        $this->assertEquals(400.0, $limit['limit_amount']);
    }
}
