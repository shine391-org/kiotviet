<?php

namespace Tests\Services;

use App\Services\Products\ProductService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use InvalidArgumentException;

class ProductServiceTest extends CIUnitTestCase
{
    private ProductService $service;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetSchema();
        $this->service = new ProductService();
    }

    public function test_list_returns_products_with_categories_and_variants(): void
    {
        $productId = $this->seedProduct(['code' => 'P001', 'name' => 'Product 1']);
        $this->seedCategoryLink($productId, 3);
        $this->seedVariant($productId, 'Red');

        $result = $this->service->list(['page' => 1, 'limit' => 5, 'include_variants' => true]);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
        $this->assertSame([3], $result['data'][0]['category_ids']);
        $this->assertNotEmpty($result['data'][0]['variants']);
        $this->assertSame(1, $result['pagination']['total']);
    }

    public function test_create_persists_product(): void
    {
        $result = $this->service->create(['code' => 'P100', 'name' => 'New product']);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['data']['id']);
        $row = $this->db->table('db_products')->where('id', $result['data']['id'])->get()->getRowArray();
        $this->assertSame('P100', $row['code']);
    }

    public function test_create_duplicate_code_throws(): void
    {
        $this->service->create(['code' => 'DUP', 'name' => 'One']);
        $this->expectException(InvalidArgumentException::class);
        $this->service->create(['code' => 'DUP', 'name' => 'Two']);
    }

    public function test_update_changes_fields(): void
    {
        $id = $this->seedProduct(['code' => 'UP1', 'name' => 'Old']);

        $updated = $this->service->update($id, ['name' => 'Updated', 'status' => 'inactive']);

        $this->assertTrue($updated['success']);
        $row = $this->db->table('db_products')->where('id', $id)->get()->getRowArray();
        $this->assertSame('inactive', $row['status']);
    }

    public function test_delete_soft_deletes_product(): void
    {
        $id = $this->seedProduct(['code' => 'DEL', 'name' => 'Delete me']);

        $deleted = $this->service->delete($id);

        $this->assertTrue($deleted['success']);

        $row = $this->db->table('db_products')->where('id', $id)->get()->getRowArray();
        $this->assertNotNull($row['deleted_at']);
    }

    public function test_validation_error_on_create(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->create([]);
    }

    public function test_validation_error_on_update_without_fields(): void
    {
        $id = $this->seedProduct(['code' => 'UP2', 'name' => 'No change']);
        $this->expectException(InvalidArgumentException::class);
        $this->service->update($id, []);
    }

    public function test_list_with_invalid_filters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->list(['page' => 0]);
    }

    public function test_update_non_existent(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->service->update(999, ['name' => 'none']);
    }

    private function resetSchema(): void
    {
        $this->db->query('DROP TABLE IF EXISTS db_product_variants_v2');
        $this->db->query('DROP TABLE IF EXISTS db_product_category_links');
        $this->db->query('DROP TABLE IF EXISTS db_products');

        $this->db->query('CREATE TABLE db_products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_type TEXT,
            code TEXT,
            barcode TEXT,
            name TEXT,
            status TEXT,
            selling_price REAL,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )');

        $this->db->query('CREATE TABLE db_product_category_links (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER,
            category_id INTEGER,
            created_at TEXT
        )');

        $this->db->query('CREATE TABLE db_product_variants_v2 (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
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
        )');
    }

    private function seedProduct(array $data): int
    {
        $payload = array_merge([
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
        return (int) $this->db->insertID();
    }

    private function seedCategoryLink(int $productId, int $categoryId): void
    {
        $this->db->table('db_product_category_links')->insert([
            'product_id' => $productId,
            'category_id' => $categoryId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function seedVariant(int $productId, string $name): void
    {
        $this->db->table('db_product_variants_v2')->insert([
            'product_id' => $productId,
            'variant_name' => $name,
            'variant_signature' => $name,
            'sku' => $name,
            'barcode' => null,
            'price' => 10,
            'cost_price' => 5,
            'stock_quantity' => 2,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ]);
    }
}
