<?php

namespace Tests\Integration\Customers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\CustomerSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Customer API integration
 * @agent-pattern: Integration test with DevDatabaseTrait + FeatureTestTrait
 */
class CustomerApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTestTrait;
    use DevDatabaseTrait;
    use CustomerSchemaTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCustomerSchema();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function test_create_customer_success(): void
    {
        $payload = [
            'name' => 'Công ty ABC',
            'customer_type' => 'COMPANY',
            'tax_code' => '0123456789',
            'invoice_company_name' => 'ABC Holdings',
            'invoice_phone' => '0909998888',
            'bank_account' => '1234567890',
            'bank_name' => 'VCB',
        ];

        $res = $this->withHeaders($this->authHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]))
            ->withBody(json_encode($payload))
            ->post('api/customers');

        $res->assertStatus(201);
        $body = $this->decodeResponse($res);
        $this->assertTrue($body['success']);
        $this->assertEquals('0123456789', $body['data']['tax_code']);

        $row = $this->db->table('customers')->where('tax_code', '0123456789')->get()->getRowArray();
        $this->assertNotEmpty($row);
        $this->assertEquals('COMPANY', $row['customer_type']);
        $this->assertEquals('VCB', $row['bank_name']);
    }

    public function test_create_minimal_customer_success(): void
    {
        $res = $this->withHeaders($this->authHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]))
            ->withBody(json_encode(['name' => 'Nguyen Van A']))
            ->post('api/customers');

        $res->assertStatus(201);
        $body = $this->decodeResponse($res);
        $this->assertTrue($body['success']);
        $this->assertEquals('INDIVIDUAL', $body['data']['customer_type']);
        $this->assertNull($body['data']['tax_code'] ?? null);
    }

    public function test_duplicate_tax_code_same_org_returns_error(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('customers')->insert([
            'name' => 'Existing',
            'tax_code' => '9999999999',
            'organization_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $res = $this->withHeaders($this->authHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]))
            ->withBody(json_encode(['name' => 'Dup', 'tax_code' => '9999999999']))
            ->post('api/customers');

        $res->assertStatus(400);
        $body = $this->decodeResponse($res);
        $message = $body['message'] ?? ($body['messages']['error'] ?? '');
        $this->assertStringContainsString('Tax code', $message);
    }

    public function test_invalid_enum_values_rejected(): void
    {
        $res = $this->withHeaders($this->authHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]))
            ->withBody(json_encode(['name' => 'Bad Enum', 'customer_type' => 'INVALID']))
            ->post('api/customers');

        $res->assertStatus(400);
        $body = $this->decodeResponse($res);
        $message = $body['message'] ?? ($body['messages']['error'] ?? '');
        $this->assertStringContainsString('customer_type', $message);
    }

    private function decodeResponse($response): array
    {
        $body = trim(strip_tags((string) $response->getBody()));
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : ['__raw' => $body];
    }
}
