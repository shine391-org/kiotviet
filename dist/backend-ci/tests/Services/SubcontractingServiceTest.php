<?php

namespace Tests\Services;

use App\Services\Manufacturing\SubcontractingService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: SubcontractingService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class SubcontractingServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private SubcontractingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->service = new SubcontractingService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_issues_materials_and_receives_product()
    {
        $order = $this->service->create([
            'supplier_id' => 1,
            'product_id' => 10,
            'quantity' => 2,
            'materials' => [
                ['material_product_id' => 100, 'quantity' => 4],
            ],
        ])['data'];

        $issued = $this->service->issueMaterials($order['id']);
        $this->assertEquals('materials_issued', $issued['data']['status']);

        $done = $this->service->receiveProduct($order['id']);
        $this->assertEquals('completed', $done['data']['status']);
    }
}
