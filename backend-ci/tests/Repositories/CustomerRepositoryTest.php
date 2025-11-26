<?php

namespace Tests\Repositories;

use App\Repositories\Customers\CustomerRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\CustomerSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/** @agent-test: CustomerRepository @agent-pattern: Repository coverage */
class CustomerRepositoryTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CustomerSchemaTrait;

    private CustomerRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCustomerSchema();
        $this->repo = new CustomerRepository(null, $this->db);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_filters_by_customer_type_and_gender(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('customers')->insertBatch([
            ['name' => 'Alice', 'customer_type' => 'INDIVIDUAL', 'gender' => 'FEMALE', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Beta LLC', 'customer_type' => 'COMPANY', 'gender' => 'MALE', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Carol', 'customer_type' => 'INDIVIDUAL', 'gender' => 'FEMALE', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $rows = $this->repo->findAll(['customer_type' => 'INDIVIDUAL', 'gender' => 'FEMALE']);
        $this->assertCount(2, $rows);

        $rows = $this->repo->findAll(['customer_type' => 'COMPANY']);
        $this->assertCount(1, $rows);
    }

    /** @test */
    public function it_detects_tax_code_exists_with_exclusion(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('customers')->insert([
            'name' => 'ACME',
            'tax_code' => '1234567890',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $existingId = (int) $this->db->insertID();

        $this->assertTrue($this->repo->taxCodeExists('1234567890', null, 1));
        $this->assertFalse($this->repo->taxCodeExists('1234567890', $existingId, 1));
    }
}
