<?php

namespace Tests\Services;

use App\Services\ProductMedia\ProductMediaService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

class ProductMediaServiceTest extends CIUnitTestCase
{
    private ProductMediaService $service;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetSchema();
        $this->service = new ProductMediaService();
    }

    private function resetSchema(): void
    {
        $auto = strtoupper($this->db->DBDriver ?? '') === 'SQLITE3' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';
        $this->db->query('DROP TABLE IF EXISTS db_product_images');
        $this->db->query('DROP TABLE IF EXISTS db_product_variants_v2');
        $this->db->query('DROP TABLE IF EXISTS db_products');

        $this->db->query("CREATE TABLE db_products (
            id INTEGER PRIMARY KEY {$auto},
            code TEXT,
            deleted_at TEXT
        )");

        $this->db->query("CREATE TABLE db_product_variants_v2 (
            id INTEGER PRIMARY KEY {$auto},
            product_id INTEGER,
            sku TEXT,
            deleted_at TEXT
        )");

        $this->db->query("CREATE TABLE db_product_images (
            id INTEGER PRIMARY KEY {$auto},
            product_id INTEGER,
            variant_id INTEGER,
            image_url TEXT,
            created_at TEXT,
            deleted_at TEXT,
            is_primary INTEGER,
            sort_order INTEGER
        )");
    }

    public function test_library_returns_images(): void
    {
        $this->db->table('db_product_images')->insert([
            'image_url' => 'img1.jpg',
            'created_at' => '2023-01-01 10:00:00',
            'is_primary' => 0,
            'sort_order' => 1
        ]);

        $result = $this->service->library(['limit' => 10, 'offset' => 0, 'entity_id' => 1]);
        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals(1, $result['pagination']['total']);
        $this->assertFalse($result['data'][0]['is_attached']); // Not attached
    }

    public function test_library_marks_attached(): void
    {
        $this->db->table('db_product_images')->insert([
            'image_url' => 'img1.jpg',
            'product_id' => 1,
            'created_at' => '2023-01-01 10:00:00'
        ]);

        $result = $this->service->library(['entity_id' => 1]);
        $this->assertTrue($result['data'][0]['is_attached']);
    }

    public function test_byDate_filters(): void
    {
        $this->db->table('db_product_images')->insert([
            'image_url' => 'img1.jpg',
            'created_at' => '2023-01-01 10:00:00'
        ]);
        $this->db->table('db_product_images')->insert([
            'image_url' => 'img2.jpg',
            'created_at' => '2023-02-01 10:00:00'
        ]);

        $result = $this->service->byDate(['year' => 2023, 'month' => 1]);
        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('img1.jpg', $result['data'][0]['image_url']);
    }

    public function test_searchSku_short_query_returns_empty(): void
    {
        $result = $this->service->searchSku(['sku' => 'a']);
        $this->assertCount(0, $result['data']);
    }

    public function test_searchSku_finds_match(): void
    {
        $this->db->table('db_products')->insert(['code' => 'PROD1', 'id' => 1]);
        $this->db->table('db_product_images')->insert(['product_id' => 1, 'image_url' => 'found.jpg']);

        $result = $this->service->searchSku(['sku' => 'PROD1']);
        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('found.jpg', $result['data'][0]['image_url']);
    }
}
