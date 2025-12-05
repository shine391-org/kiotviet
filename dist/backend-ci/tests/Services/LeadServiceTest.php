<?php

namespace Tests\Services;

use App\Services\CRM\LeadService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: LeadService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class LeadServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private LeadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->service = new LeadService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_and_converts_lead()
    {
        $lead = $this->service->create([
            'name' => 'New Lead',
            'email' => 'lead@example.com',
            'phone' => '0909',
        ]);
        $this->assertTrue($lead['success']);

        $convert = $this->service->convertToCustomer($lead['data']['id']);
        $this->assertTrue($convert['success']);
        $this->assertGreaterThan(0, $convert['data']['customer_id']);
    }
}
