<?php

namespace Tests\Services;

use App\Repositories\Customers\CustomerRepository;
use App\Services\Customers\CustomerService;
use App\Transformers\CustomerTransformer;
use App\Validators\CustomerValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use Tests\Support\Database\CustomerSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: CustomerService
 * @agent-pattern: MySQL-only test with DevDatabaseTrait
 */
class CustomerServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CustomerSchemaTrait;

    private CustomerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCustomerSchema();

        $repo = new CustomerRepository(null, $this->db);
        $validator = new CustomerValidator();
        $transformer = new CustomerTransformer();
        $this->service = new CustomerService($repo, $validator, $transformer);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_customer_with_full_payload(): void
    {
        $result = $this->service->create([
            'name' => 'Công ty ABC',
            'customer_type' => 'COMPANY',
            'tax_code' => '0123456789',
            'phone' => '0901112222',
            'phone2' => '0903334444',
            'gender' => 'MALE',
            'facebook' => 'https://facebook.com/abc',
            'company_name' => 'ABC Holdings',
            'buyer_name' => 'Nguyen Van B',
            'invoice_company_name' => 'ABC Holdings',
            'invoice_address' => '123 Main St',
            'invoice_province' => 'Hanoi',
            'invoice_district' => 'Ba Dinh',
            'invoice_ward' => 'Cong Vi',
            'invoice_email' => 'invoice@abc.com',
            'invoice_phone' => '0987654321',
            'cccd_cmnd' => '012345678901',
            'id_number' => 'P1234567',
            'bank_account' => '123456789012',
            'bank_name' => 'VCB',
            'notes' => 'VIP customer',
        ]);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['data']['id']);
        $stored = $this->db->table('customers')->where('id', $result['data']['id'])->get()->getRowArray();
        $this->assertEquals('0123456789', $stored['tax_code']);
        $this->assertEquals('COMPANY', $stored['customer_type']);
        $this->assertEquals('VCB', $stored['bank_name']);
        $this->assertEquals('VIP customer', $stored['notes']);
    }

    /** @test */
    public function it_rejects_invalid_tax_code(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->create([
            'name' => 'Bad Tax',
            'tax_code' => 'ABC123',
        ]);
    }

    /** @test */
    public function it_blocks_duplicate_tax_code_per_org(): void
    {
        $this->service->create([
            'name' => 'First',
            'tax_code' => '1234567890',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->create([
            'name' => 'Second',
            'tax_code' => '1234567890',
        ]);
    }

    /** @test */
    public function it_lists_customers_with_search_and_pagination(): void
    {
        $this->service->create(['name' => 'ACME Corp', 'tax_code' => '1111111111', 'customer_type' => 'COMPANY']);
        $this->service->create(['name' => 'Beta Retail', 'tax_code' => '2222222222', 'customer_type' => 'INDIVIDUAL']);
        $this->service->create(['name' => 'Acme Retail', 'tax_code' => '3333333333', 'customer_type' => 'HOUSEHOLD']);

        $result = $this->service->list(['search' => 'acme', 'limit' => 1, 'page' => 1]);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals(2, $result['pagination']['total']);
        $this->assertEquals(2, $result['pagination']['total_pages']);
    }
}
