<?php

namespace Tests\Feature;

use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

class ProductsApiE2ETest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetSchema();
        $this->seedProduct(1, ['code' => 'AO01', 'name' => 'Áo thun', 'status' => 'active']);
    }

    public function test_get_products_list(): void
    {
        $response = $this->get('api/products');
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
        $response->assertJSONPath('pagination.page', 1);
        $response->assertJSONPath('data.0.code', 'AO01');
    }

    public function test_get_products_list_search(): void
    {
        $response = $this->get('api/products?search=ao');
        $response->assertStatus(200);
        $response->assertJSONPath('data.0.code', 'AO01');
    }

    public function test_get_product_detail(): void
    {
        $response = $this->get('api/products/1');
        $response->assertStatus(200);
        $response->assertJSONPath('data.id', 1);
        $response->assertJSONPath('data.code', 'AO01');
    }

    public function test_post_create_product(): void
    {
        $payload = ['code' => 'AO02', 'name' => 'Áo polo'];
        $response = $this->withBody(json_encode($payload), 'application/json')->post('api/products');
        $response->assertStatus(201);
        $response->assertJSONFragment(['success' => true]);
        $response->assertJSONPath('data.code', 'AO02');
    }

    public function test_put_update_product(): void
    {
        $response = $this->withBody(json_encode(['name' => 'Áo updated']), 'application/json')->put('api/products/1');
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
    }

    public function test_delete_product(): void
    {
        $response = $this->delete('api/products/1');
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
    }

    private function resetSchema(): void
    {
        $auto = strtoupper($this->db->DBDriver ?? '') === 'SQLITE3' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';
        $this->db->query('DROP TABLE IF EXISTS db_product_variants_v2');
        $this->db->query('DROP TABLE IF EXISTS db_product_category_links');
        $this->db->query('DROP TABLE IF EXISTS db_products');

        $this->db->query("CREATE TABLE db_products (
            id INTEGER PRIMARY KEY {$auto},
            product_type TEXT,
            code TEXT,
            barcode TEXT,
            name TEXT,
            status TEXT,
            selling_price REAL,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )");

        $this->db->query("CREATE TABLE db_product_category_links (
            id INTEGER PRIMARY KEY {$auto},
            product_id INTEGER,
            category_id INTEGER,
            created_at TEXT
        )");

        $this->db->query("CREATE TABLE db_product_variants_v2 (
            id INTEGER PRIMARY KEY {$auto},
            product_id INTEGER,
            variant_name TEXT,
            variant_signature TEXT,
            sku TEXT,
            barcode TEXT,
            price REAL,
            cost_price REAL,
            stock_quantity REAL,
            min_stock REAL,
            max_stock REAL,
            image_url TEXT,
            attributes TEXT,
            status TEXT,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )");
    }

    private function seedProduct(int $id, array $data): void
    {
        $payload = array_merge([
            'id' => $id,
            'product_type' => null,
            'code' => 'P' . random_int(1000, 9999),
            'barcode' => null,
            'name' => 'Sample',
            'status' => 'active',
            'selling_price' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ], $data);

        $this->db->table('db_products')->insert($payload);
    }
}
