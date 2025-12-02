<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\CompleteSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Dashboard API
 * @agent-pattern: Integration test with DevDatabaseTrait + FeatureTestTrait
 */
class DashboardApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTestTrait;
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->seedDashboardData();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function kpi_endpoint_returns_data()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->get('/api/dashboard/kpi-today');

        $body = $this->decodeResponse($response);
        $this->assertTrue($response->isOK(), 'Expected HTTP 200 response');
        $this->assertTrue($body['success'] ?? false);
        $this->assertArrayHasKey('netRevenue', $body['data']);
    }

    /** @test */
    public function revenue_chart_endpoint_returns_chart()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->get('/api/dashboard/revenue-chart');

        $body = $this->decodeResponse($response);
        $this->assertTrue($body['success'] ?? false);
        $this->assertArrayHasKey('chart', $body);
        $this->assertGreaterThan(0, $body['chart']['total'] ?? 0);
    }

    /** @test */
    public function top_products_endpoint_returns_items()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->get('/api/dashboard/top-products');

        $body = $this->decodeResponse($response);
        $this->assertTrue($body['success'] ?? false);
        $this->assertNotEmpty($body['items'] ?? []);
    }

    /** @test */
    public function activities_endpoint_returns_feed()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->get('/api/dashboard/activities?limit=2');

        $body = $this->decodeResponse($response);
        $this->assertTrue($body['success'] ?? false);
        $this->assertCount(2, $body['items'] ?? []);
    }

    private function seedDashboardData(): void
    {
        $now = date('Y-m-d H:i:s');
        $productId = (int) $this->db->table('products')->insert([
            'product_type' => 'goods',
            'code' => 'API-001',
            'name' => 'Core Widget',
            'slug' => 'core-widget',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $customerId = (int) $this->db->table('customers')->insert([
            'organization_id' => 1,
            'name' => 'API Customer',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->db->table('users')->insert([
            'id' => 1,
            'username' => 'devadmin',
            'email' => 'admin@lanocrm.local',
            'password' => password_hash('123aA@hai', PASSWORD_DEFAULT),
            'full_name' => 'Dev Admin',
            'branch_id' => 1,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $today = date('Y-m-d');

        $orderId = $this->insertOrder($customerId, 'completed', $today, 1000.0, $now);
        $this->insertOrderItem($orderId, $productId, 1000.0, 1.0, 500.0, $now);

        $this->db->table('cash_transactions')->insertBatch([
            [
                'account_name' => 'API cash',
                'amount' => 200.0,
                'branch_id' => 1,
                'category' => 'sales',
                'created_at' => $now,
                'updated_at' => $now,
                'transaction_date' => $today,
                'type' => 'RECEIPT',
                'status' => 'approved',
                'created_by' => 1,
                'created_by_name' => 'Dev Admin',
                'payment_method' => 'cash',
            ],
            [
                'account_name' => 'API cash',
                'amount' => 120.0,
                'branch_id' => 1,
                'category' => 'refund',
                'created_at' => $now,
                'updated_at' => $now,
                'transaction_date' => $today,
                'type' => 'PAYMENT',
                'status' => 'approved',
                'created_by' => 1,
                'created_by_name' => 'Dev Admin',
                'payment_method' => 'cash',
            ],
        ]);
    }

    private function insertOrder(int $customerId, string $status, string $date, float $total, string $now): int
    {
        $this->db->table('orders')->insert([
            'customer_id' => $customerId,
            'branch_id' => 1,
            'status' => $status,
            'order_date' => $date,
            'total' => $total,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->insertID();
    }

    private function insertOrderItem(int $orderId, int $productId, float $price, float $qty, float $base, string $now): void
    {
        $this->db->table('order_items')->insert([
            'order_id' => $orderId,
            'product_id' => $productId,
            'variant_id' => null,
            'quantity' => $qty,
            'base_price' => $base,
            'final_price' => $price,
            'price_list_id' => null,
            'price_list_name' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function decodeResponse($response): array
    {
        $raw = (string) $response->getBody();
        if (strpos($raw, '<!DOCTYPE html') !== false) {
            if (preg_match('/<p>(.*?)<\/p>/s', $raw, $matches)) {
                $raw = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
            } elseif (preg_match('/<pre>(.*?)<\/pre>/s', $raw, $matches)) {
                $raw = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
            }
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
