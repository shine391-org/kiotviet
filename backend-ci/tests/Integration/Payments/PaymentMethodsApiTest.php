<?php

namespace Tests\Integration\Payments;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\PaymentMethodSchemaTrait;

/**
 * @agent-test: Payment methods API integration
 * @agent-pattern: Standard API integration test - COPY THIS
 */
class PaymentMethodsApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTestTrait;
    use PaymentMethodSchemaTrait;

    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetPaymentSchema();
        $this->setUpAuthToken();
    }

    public function test_list_returns_active_methods_only_and_sorted(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('payment_methods')->insertBatch([
            ['code' => 'CASH', 'name' => 'Cash', 'is_active' => 1, 'display_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'CARD', 'name' => 'Card', 'is_active' => 1, 'display_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'HIDDEN', 'name' => 'Hidden', 'is_active' => 0, 'display_order' => 0, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $res = $this->withHeaders($this->authHeaders(['Accept' => 'application/json']))
            ->get('api/payment-methods');

        $res->assertStatus(200);
        $payload = $this->decodeResponse($res);
        $this->assertTrue($payload['success']);
        $this->assertCount(2, $payload['data']);
        $this->assertEquals('CARD', $payload['data'][0]['code']);
        $this->assertEquals('CASH', $payload['data'][1]['code']);
    }

    public function test_create_payment_method_success(): void
    {
        $body = [
            'code' => 'BANK_TRANSFER',
            'name' => 'Chuyển khoản',
            'description' => 'Thanh toán qua ngân hàng',
            'display_order' => 3,
            'name_translations' => ['vi' => 'Chuyển khoản', 'en' => 'Bank Transfer'],
        ];

        $res = $this->withHeaders($this->authHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]))
            ->withBody(json_encode($body))
            ->post('api/payment-methods');

        $res->assertStatus(201);
        $payload = $this->decodeResponse($res);
        $this->assertTrue($payload['success']);
        $this->assertEquals('BANK_TRANSFER', $payload['data']['code']);

        $row = $this->db->table('payment_methods')->where('code', 'BANK_TRANSFER')->get()->getRowArray();
        $this->assertNotEmpty($row);
    }

    public function test_delete_blocked_when_used_in_orders(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('payment_methods')->insert([
            'code' => 'COD',
            'name' => 'COD',
            'is_active' => 1,
            'display_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $methodId = (int) $this->db->insertID();

        $this->db->table('orders')->insert([
            'payment_method' => 'COD',
            'total' => 150000,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $res = $this->withHeaders($this->authHeaders(['Accept' => 'application/json']))
            ->delete("api/payment-methods/{$methodId}");

        $res->assertStatus(400);
        $payload = $this->decodeResponse($res);
        $message = $payload['message'] ?? ($payload['messages']['error'] ?? '');
        $this->assertStringContainsString('Cannot delete payment method', $message);
    }

    private function decodeResponse($res): array
    {
        $body = $res->getBody();
        $body = trim(strip_tags((string) $body));
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : [];
    }
}
