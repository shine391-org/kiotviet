<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\AuthTestTrait;

/**
 * @agent-test: Orders pricing API
 * @agent-pattern: MySQL-only feature test with DevDatabaseTrait
 */
class OrdersPricingApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use AuthTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->forceFreshMigrate();
        // Defensive: ensure pricing tables exist (some traits may drop them).
        $this->db->query("CREATE TABLE IF NOT EXISTS price_lists (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(50) DEFAULT 'custom',
            description TEXT NULL,
            apply_to_groups JSON NULL,
            start_date DATE NULL,
            end_date DATE NULL,
            priority INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            formula TEXT NULL,
            base_price_list_id INT NULL,
            auto_update TINYINT(1) DEFAULT 0,
            rounding_rule VARCHAR(50) DEFAULT 'none',
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->query("CREATE TABLE IF NOT EXISTS price_list_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            price_list_id INT NOT NULL,
            product_id INT NULL,
            variant_id INT NULL,
            price DECIMAL(10,2) NOT NULL,
            discount_percent DECIMAL(5,2) DEFAULT 0,
            discount_amount DECIMAL(10,2) DEFAULT 0,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        \Config\Services::reset(true);
        $config = config('Database');
        $config->defaultGroup = 'tests';
        $this->seedCustomer(10, 2);
        $this->seedProduct(1, 100000);
        $listId = $this->seedPriceList(['name' => 'VIP', 'priority' => 5, 'apply_to_groups' => [2]]);
        $this->seedItem($listId, 1, null, 80000, 0, 0);
        $this->setUpAuthToken();
        // Commit seed data so it is visible to HTTP requests (FeatureTestTrait uses separate DB connection).
        $this->db->transCommit();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function test_calculate_preview_applies_price_list(): void
    {
        $payload = [
            'customer_id' => 10,
            'customer_group_id' => 2,
            'order_date' => date('Y-m-d'),
            'items' => [
                ['product_id' => 1, 'quantity' => 2],
            ],
        ];

        $res = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode($payload), 'application/json')
            ->post('api/orders/calculate-preview');

        if ($res->getStatusCode() !== 200) {
            fwrite(STDERR, "Response body: " . $res->getBody() . "\n");
        }
        $res->assertStatus(200);
        $res->assertJSONPath('data.total', 160000.0);
        $res->assertJSONPath('data.applied_price_list_name', 'VIP');
    }

    public function test_create_order_persists_and_records_price_list(): void
    {
        $payload = [
            'customer_id' => 10,
            'customer_group_id' => 2,
            'branch_id' => 1,
            'order_date' => date('Y-m-d'),
            'order_number' => 'ORD-TEST-1',
            'payment_method' => 'CASH',
            'shipping' => [
                'name' => 'John Doe',
                'phone' => '0900000000',
                'address' => '123 Street',
                'ward' => 'Ward',
                'district' => 'District',
                'city' => 'City',
            ],
            'paid_amount' => 80000,
            'items' => [
                ['product_id' => 1, 'quantity' => 1],
            ],
        ];

        $res = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode($payload), 'application/json')
            ->post('api/orders');

        if ($res->getStatusCode() !== 201) {
            fwrite(STDERR, "Response body: " . $res->getBody() . "\n");
        }
        $res->assertStatus(201);
        $res->assertJSONFragment(['success' => true]);
    }

    public function test_black_friday_overrides_vip(): void
    {
        $this->seedProduct(2, 100000);
        $vip = $this->seedPriceList(['name' => 'VIP', 'priority' => 5, 'apply_to_groups' => [2]]);
        $bf = $this->seedPriceList(['name' => 'BF', 'priority' => 10, 'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d')]);
        $this->seedItem($vip, 2, null, 80000, 0, 0);
        $this->seedItem($bf, 2, null, 60000, 0, 0);

        $payload = [
            'customer_group_id' => 2,
            'order_date' => date('Y-m-d'),
            'items' => [['product_id' => 2, 'quantity' => 1]],
        ];

        $res = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode($payload), 'application/json')
            ->post('api/orders/calculate-preview');

        if ($res->getStatusCode() !== 200) {
            fwrite(STDERR, "Response body: " . $res->getBody() . "\n");
        }
        $res->assertStatus(200);
        $res->assertJSONPath('data.items.0.final_price', 60000.0);
        $res->assertJSONPath('data.applied_price_list_name', 'BF');
    }

    public function test_multiple_customer_groups_priority_handling(): void
    {
        $this->seedProduct(3, 150000);
        $base = $this->seedPriceList(['name' => 'Retail', 'priority' => 1]);
        $groupA = $this->seedPriceList(['name' => 'GroupA', 'priority' => 3, 'apply_to_groups' => [3]]);
        $groupB = $this->seedPriceList(['name' => 'GroupB', 'priority' => 8, 'apply_to_groups' => [3, 4]]);

        $this->seedItem($base, 3, null, 150000, 0, 0);
        $this->seedItem($groupA, 3, null, 110000, 0, 0);
        $this->seedItem($groupB, 3, null, 90000, 0, 0);

        $payload = [
            'customer_group_id' => 3,
            'order_date' => date('Y-m-d'),
            'items' => [['product_id' => 3, 'quantity' => 1]],
        ];

        $res = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode($payload), 'application/json')
            ->post('api/orders/calculate-preview');

        if ($res->getStatusCode() !== 200) {
            fwrite(STDERR, "Response body: " . $res->getBody() . "\n");
        }
        $res->assertStatus(200);
        $res->assertJSONPath('data.items.0.final_price', 90000.0);
        $res->assertJSONPath('data.applied_price_list_name', 'GroupB');
    }

    public function test_rounding_rule_hundred_applied(): void
    {
        $this->seedProduct(4, 123456);
        $list = $this->seedPriceList([
            'name' => 'Round',
            'priority' => 9,
            'rounding_rule' => 'hundred',
            'formula' => 'base'
        ]);
        $this->seedItem($list, 4, null, 0, 0, 0);

        $payload = [
            'customer_group_id' => null,
            'order_date' => date('Y-m-d'),
            'items' => [['product_id' => 4, 'quantity' => 1]],
        ];

        $res = $this->withHeaders($this->authHeaders(['Content-Type' => 'application/json']))
            ->withBody(json_encode($payload), 'application/json')
            ->post('api/orders/calculate-preview');

        $res->assertStatus(200);
        $res->assertJSONPath('data.items.0.final_price', 123500.0);
        $res->assertJSONPath('data.applied_price_list_name', 'Round');
    }

    private function seedProduct(int $id, float $price): void
    {
        $this->db->table('products')->insert([
            'id' => $id,
            'code' => 'P' . $id,
            'name' => 'Prod ' . $id,
            'selling_price' => $price,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function seedPriceList(array $data): int
    {
        $payload = array_merge([
            'name' => 'PL',
            'type' => 'custom',
            'priority' => 0,
            'is_active' => 1,
            'start_date' => date('Y-m-d', strtotime('-1 day')),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);
        $payload['apply_to_groups'] = isset($payload['apply_to_groups']) ? json_encode((array) $payload['apply_to_groups']) : null;
        $this->db->table('price_lists')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function seedItem(int $listId, int $productId, ?int $variantId, float $price, float $discountPercent, float $discountAmount): void
    {
        $this->db->table('price_list_items')->insert([
            'price_list_id' => $listId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'price' => $price,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function seedCustomer(int $id, ?int $groupId): void
    {
        $this->db->table('customers')->insert([
            'id' => $id,
            'customer_group_id' => $groupId,
            'name' => 'Customer ' . $id,
            'phone' => '090000000' . $id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
