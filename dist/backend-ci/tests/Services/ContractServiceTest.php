<?php

namespace Tests\Services;

use App\Services\Contracts\ContractService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: ContractService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class ContractServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private ContractService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->service = new ContractService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_contract_with_terms_and_template()
    {
        $template = $this->service->createTemplate(['name' => 'Standard', 'terms' => 'Base'])['data'];

        $result = $this->service->create([
            'customer_id' => 1,
            'template_id' => $template['id'],
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'value' => 1500.50,
            'auto_renew' => true,
            'terms' => [
                ['description' => 'Deliver within 7 days'],
                ['description' => 'Warranty 12 months', 'is_completed' => true],
            ],
        ]);

        $this->assertTrue($result['success']);
        $contract = $result['data'];
        $this->assertGreaterThan(0, $contract['id']);
        $this->assertEquals($template['id'], $contract['template_id']);
        $this->assertTrue($contract['auto_renew']);
        $this->assertCount(2, $contract['terms']);

        $row = $this->db->table('contracts')->where('id', $contract['id'])->get()->getRowArray();
        $this->assertNotNull($row);
    }

    /** @test */
    public function it_activates_then_closes_contract()
    {
        $contract = $this->service->create(['customer_id' => 1, 'terms' => []])['data'];

        $active = $this->service->activate($contract['id']);
        $this->assertEquals('active', $active['data']['status']);

        $closed = $this->service->close($contract['id']);
        $this->assertEquals('closed', $closed['data']['status']);
    }

    /** @test */
    public function it_validates_template_existence()
    {
        $this->expectException(\RuntimeException::class);
        $this->service->create(['template_id' => 999, 'terms' => []]);
    }

    /** @test */
    public function it_validates_end_date()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->create([
            'start_date' => '2025-02-01',
            'end_date' => '2025-01-01',
            'terms' => [],
        ]);
    }

    /** @test */
    public function it_returns_renew_stub_message()
    {
        $contract = $this->service->create(['customer_id' => 1, 'terms' => []])['data'];
        $result = $this->service->renew($contract['id']);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('message', $result);
    }
}
