<?php

namespace Tests\Integration\Products;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\AuthTestTrait;

/** @agent-test: Product price calculation API (MySQL) @agent-pattern: API integration test */
class ProductPriceCalculationApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTestTrait;

    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->ensureAuxTables();
        $this->truncateTables();
        $this->setUpAuthToken();
    }

    public function test_get_single_product_with_price_list(): void
    {
        $productId = $this->seedProduct('P100', 100000);
        $priceListId = $this->seedPriceList(['name' => 'VIP 10%', 'priority' => 5]);
        $this->seedPriceListItem($priceListId, $productId, 0, 10, 0);

        $res = $this->withHeaders($this->authHeaders(['Accept' => 'application/json']))
            ->get("api/products/{$productId}?price_list_id={$priceListId}");

        $res->assertStatus(200);
        $payload = $this->decodeResponse($res);
        $this->assertEquals($priceListId, $payload['data']['applied_price_list_id']);
        $this->assertEquals(90000.0, $payload['data']['price_after_discount']);
        $this->assertEquals(100000.0, $payload['data']['base_price']);
    }

    public function test_get_product_list_with_price_list_applies_discount(): void
    {
        $p1 = $this->seedProduct('P1', 100000);
        $p2 = $this->seedProduct('P2', 200000);
        $p3 = $this->seedProduct('P3', 300000);
        $priceListId = $this->seedPriceList(['name' => 'Minus 50k', 'priority' => 5]);
        $this->seedPriceListItem($priceListId, $p1, 0, 0, 50000);
        $this->seedPriceListItem($priceListId, $p2, 0, 0, 50000);
        $this->seedPriceListItem($priceListId, $p3, 0, 0, 50000);

        $res = $this->withHeaders($this->authHeaders(['Accept' => 'application/json']))
            ->get("api/products?price_list_id={$priceListId}&limit=10");
        $res->assertStatus(200);

        $payload = $this->decodeResponse($res);
        $this->assertTrue($payload['success']);
        $map = [];
        foreach ($payload['data'] as $row) {
            $map[$row['code']] = $row['price_after_discount'] ?? null;
        }

        $this->assertEquals(50000.0, $map['P1']);
        $this->assertEquals(150000.0, $map['P2']);
        $this->assertEquals(250000.0, $map['P3']);
    }

    public function test_price_list_not_found_returns_original_price(): void
    {
        $productId = $this->seedProduct('PNF', 100000);

        $res = $this->withHeaders($this->authHeaders(['Accept' => 'application/json']))
            ->get("api/products/{$productId}?price_list_id=999999");
        $res->assertStatus(200);
        $payload = $this->decodeResponse($res);
        $this->assertEquals(100000.0, $payload['data']['base_price'] ?? null);
        $this->assertNull($payload['data']['applied_price_list_id'] ?? null);
    }

    private function truncateTables(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach (['price_list_items', 'price_lists', 'product_variants_v2', 'products'] as $table) {
                if ($this->db->tableExists($table)) {
                    $this->db->table($table)->truncate();
                }
            }
        } finally {
            $this->db->query('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function ensureAuxTables(): void
    {
        // products + db_products
        $this->db->query('CREATE TABLE IF NOT EXISTS products (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50),
            name VARCHAR(255),
            selling_price DECIMAL(14,2),
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
        $this->db->query('CREATE TABLE IF NOT EXISTS db_products LIKE products;');

        // price lists
        $this->db->query('CREATE TABLE IF NOT EXISTS price_lists (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
            type VARCHAR(50),
            description TEXT,
            apply_to_groups JSON NULL,
            start_date DATE NULL,
            end_date DATE NULL,
            priority INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            formula TEXT NULL,
            base_price_list_id INT NULL,
            auto_update TINYINT(1) DEFAULT 0,
            rounding_rule VARCHAR(50) NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
        $this->db->query('CREATE TABLE IF NOT EXISTS db_price_lists LIKE price_lists;');

        $this->db->query('CREATE TABLE IF NOT EXISTS price_list_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            price_list_id INT,
            product_id INT,
            variant_id INT NULL,
            price DECIMAL(14,2) DEFAULT 0,
            discount_percent DECIMAL(8,2) DEFAULT 0,
            discount_amount DECIMAL(14,2) DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
        $this->db->query('CREATE TABLE IF NOT EXISTS db_price_list_items LIKE price_list_items;');

        // Minimal category links table
        $this->db->query('CREATE TABLE IF NOT EXISTS product_category_links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT,
            category_id INT,
            created_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
        $this->db->query('CREATE TABLE IF NOT EXISTS db_product_category_links LIKE product_category_links;');

        // Minimal variant table for lookup
        $this->db->query('CREATE TABLE IF NOT EXISTS product_variants_v2 (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            sku VARCHAR(255) NULL,
            price DECIMAL(12,2) DEFAULT 0,
            deleted_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
        $this->db->query('CREATE TABLE IF NOT EXISTS db_product_variants_v2 LIKE product_variants_v2;');

        // truncate clean state
        foreach ([
            'price_list_items','db_price_list_items',
            'price_lists','db_price_lists',
            'products','db_products',
            'product_variants_v2','db_product_variants_v2',
            'product_category_links','db_product_category_links'
        ] as $tbl) {
            if ($this->db->tableExists($tbl)) {
                $this->db->table($tbl)->truncate();
            }
        }
    }

    private function seedProduct(string $code, float $price): int
    {
        $row = [
            'code' => $code,
            'name' => $code,
            'selling_price' => $price,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ];
        $this->db->table('products')->insert($row);
        return (int) $this->db->insertID();
    }

    private function seedPriceList(array $data): int
    {
        $payload = array_merge([
            'name' => 'PL ' . random_int(100, 999),
            'type' => 'custom',
            'priority' => 0,
            'is_active' => 1,
            'start_date' => date('Y-m-d', strtotime('-1 day')),
            'end_date' => null,
            'apply_to_groups' => json_encode([]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);
        if (isset($payload['apply_to_groups']) && is_array($payload['apply_to_groups'])) {
            $payload['apply_to_groups'] = json_encode($payload['apply_to_groups']);
        }
        $this->db->table('price_lists')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function seedPriceListItem(int $priceListId, int $productId, float $price = 0, float $discountPercent = 0, float $discountAmount = 0): void
    {
        $this->db->table('price_list_items')->insert([
            'price_list_id' => $priceListId,
            'product_id' => $productId,
            'variant_id' => null,
            'price' => $price,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function decodeResponse($res): array
    {
        $body = $res->getBody();
        $body = trim(strip_tags((string) $body));
        return json_decode($body, true);
    }
}
