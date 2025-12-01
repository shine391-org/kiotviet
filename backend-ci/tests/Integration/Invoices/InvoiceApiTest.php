<?php

namespace Tests\Integration\Invoices;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\InvoiceSchemaTrait;

/**
 * @agent-test: Invoice API integration
 * @agent-pattern: Standard API integration test
 */
class InvoiceApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTestTrait;
    use DevDatabaseTrait;
    use InvoiceSchemaTrait;

    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetInvoiceSchema();
        $this->seedLookup();
        $this->setUpAuthToken();
    }

    public function test_create_invoice_success(): void
    {
        $oid1 = $this->seedOrder(1, 1, 120000);
        $oid2 = $this->seedOrder(1, 1, 30000);

        $payload = [
            'customer_id' => 1,
            'branch_id' => 1,
            'order_ids' => [$oid1, $oid2],
            'vat_rate' => 0.1,
            'created_by' => 1,
        ];

        $res = $this->withHeaders($this->authHeaders(['Accept' => 'application/json']))
            ->withBody(json_encode($payload))
            ->post('api/invoices');

        $res->assertStatus(201);
        $body = $this->decodeResponse($res);
        $this->assertTrue($body['success']);
        $this->assertEquals(150000.0, $body['data']['subtotal']);
        $this->assertEquals(15000.0, $body['data']['vat_amount']);
        $this->assertStringStartsWith('HAN01', $body['data']['invoice_number']);

        $mapped = $this->db->table('invoice_orders')->countAllResults();
        $this->assertEquals(2, $mapped);
    }

    public function test_cannot_invoice_same_order_twice(): void
    {
        $oid = $this->seedOrder(1, 1, 50000);

        $payload = [
            'customer_id' => 1,
            'branch_id' => 1,
            'order_ids' => [$oid],
            'vat_rate' => 0.1,
            'created_by' => 1,
        ];
        $this->withHeaders($this->authHeaders(['Accept' => 'application/json']))
            ->withBody(json_encode($payload))
            ->post('api/invoices');

        $res = $this->withHeaders($this->authHeaders(['Accept' => 'application/json']))
            ->withBody(json_encode($payload))
            ->post('api/invoices');

        $res->assertStatus(400);
        $body = $this->decodeResponse($res);
        $message = $body['message'] ?? ($body['messages']['error'] ?? '');
        $this->assertStringContainsString('already invoiced', $message);
    }

    private function decodeResponse($res): array
    {
        $body = trim(strip_tags((string) $res->getBody()));
        $json = json_decode($body, true);
        if (is_array($json)) {
            return $json;
        }
        return ['__raw' => $body];
    }

    private function seedOrder(int $customerId, int $branchId, float $total): int
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('orders')->insert([
            'customer_id' => $customerId,
            'branch_id' => $branchId,
            'total' => $total,
            'status' => 'completed',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $this->db->insertID();
    }

    private function seedLookup(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('customers')->insert(['id' => 1, 'name' => 'ACME', 'tax_code' => '0101234567', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('branches')->insert(['id' => 1, 'code' => 'HAN01', 'name' => 'Branch 1', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('users')->insert(['id' => 1, 'username' => 'tester', 'created_at' => $now, 'updated_at' => $now]);
    }
}
