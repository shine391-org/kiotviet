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

    public function test_library_marks_attached_images(): void
    {
        $productId = $this->insertProduct('LIB-1');
        $variantId = $this->insertVariant($productId, 'SKU-LIB');

        $productImageId = $this->insertImage(['product_id' => $productId]);
        $variantImageId = $this->insertImage(['variant_id' => $variantId, 'created_at' => date('Y-m-d H:i:s', strtotime('+1 minute'))]);
        $otherImageId = $this->insertImage(['product_id' => $this->insertProduct('LIB-2')]);

        $result = $this->service->library(['limit' => 10, 'offset' => 0, 'entity_id' => $productId]);

        $this->assertTrue($result['success']);
        $this->assertSame(3, $result['pagination']['total']);

        $map = [];
        foreach ($result['data'] as $row) {
            $map[$row['id']] = $row['is_attached'];
        }

        $this->assertTrue($map[$productImageId]);
        $this->assertTrue($map[$variantImageId]);
        $this->assertFalse($map[$otherImageId]);
    }

    public function test_search_sku_short_query_returns_empty(): void
    {
        $result = $this->service->searchSku(['sku' => 'A']);

        $this->assertTrue($result['success']);
        $this->assertSame([], $result['data']);
        $this->assertSame(0, $result['total']);
    }

    public function test_search_sku_returns_matches_and_flags_attachment(): void
    {
        $productId = $this->insertProduct('CODE-10');
        $variantId = $this->insertVariant($productId, 'SKU-10');
        $imageId = $this->insertImage(['variant_id' => $variantId]);

        $result = $this->service->searchSku(['sku' => 'SKU-1', 'entity_id' => $productId, 'limit' => 5]);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['total']);
        $this->assertSame($imageId, $result['data'][0]['id']);
        $this->assertTrue($result['data'][0]['is_attached']);
    }

    private function resetSchema(): void
    {
        $this->db->query('DROP TABLE IF EXISTS db_product_images');
        $this->db->query('DROP TABLE IF EXISTS db_product_variants_v2');
        $this->db->query('DROP TABLE IF EXISTS db_products');

        $this->db->query('CREATE TABLE db_products (
            id INTEGER PRIMARY KEY AUTO_INCREMENT,
            code TEXT,
            name TEXT,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )');

        $this->db->query('CREATE TABLE db_product_variants_v2 (
            id INTEGER PRIMARY KEY AUTO_INCREMENT,
            product_id INTEGER,
            sku TEXT,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )');

        $this->db->query('CREATE TABLE db_product_images (
            id INTEGER PRIMARY KEY AUTO_INCREMENT,
            product_id INTEGER,
            variant_id INTEGER,
            image_path TEXT,
            image_url TEXT,
            is_primary INTEGER,
            sort_order INTEGER,
            file_name TEXT,
            deleted_at TEXT,
            created_at TEXT,
            updated_at TEXT
        )');
    }

    private function insertProduct(string $code): int
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('db_products')->insert([
            'code' => $code,
            'name' => 'Product ' . $code,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);
        return (int) $this->db->insertID();
    }

    private function insertVariant(int $productId, string $sku): int
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('db_product_variants_v2')->insert([
            'product_id' => $productId,
            'sku' => $sku,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);
        return (int) $this->db->insertID();
    }

    private function insertImage(array $data = []): int
    {
        $payload = array_merge([
            'product_id' => null,
            'variant_id' => null,
            'image_path' => '/uploads/img.jpg',
            'image_url' => '/uploads/img.jpg',
            'is_primary' => 0,
            'sort_order' => 0,
            'file_name' => 'img.jpg',
            'deleted_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('db_product_images')->insert($payload);
        return (int) $this->db->insertID();
    }
}
