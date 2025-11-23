<?php

namespace Tests\Services;

use App\Services\Products\ProductService;
use App\Services\ProductVariants\ProductVariantService;
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

    public function test_create_product_fails_when_code_matches_variant_sku(): void
    {
        $productId = $this->seedProduct(['code' => 'PX1', 'name' => 'Base product']);
        $this->seedVariant($productId, 'CONFLICT');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mã sản phẩm đã tồn tại trong danh sách phiên bản');

        $this->service->create(['code' => 'CONFLICT', 'name' => 'Clashing product']);
    }

    public function test_check_code_detects_variant_collision(): void
    {
        $productId = $this->seedProduct(['code' => 'PX2', 'name' => 'Product']);
        $this->seedVariant($productId, 'SKU-123');

        $result = $this->service->checkCode('SKU-123');

        $this->assertTrue($result['exists']);
        $this->assertFalse($result['exists_in_products']);
        $this->assertTrue($result['exists_in_variants']);
        $this->assertStringContainsString('phiên bản', $result['message']);
    }

    public function test_variant_creation_rejects_sku_matching_product_code(): void
    {
        $productId = $this->seedProduct(['code' => 'PX3', 'name' => 'Prod']);
        $variantService = new ProductVariantService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SKU đã tồn tại trong danh sách sản phẩm');

        $variantService->create($productId, ['sku' => 'PX3']);
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
        $auto = strtoupper($this->db->DBDriver ?? '') === 'SQLITE3' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';
        $this->db->query('DROP TABLE IF EXISTS db_product_images');
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

        $this->db->query("CREATE TABLE db_product_images (
            id INTEGER PRIMARY KEY {$auto},
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
        )");
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

    private function seedImage(array $data = []): int
    {
        $payload = array_merge([
            'product_id' => null,
            'variant_id' => null,
            'image_path' => '/uploads/test.jpg',
            'image_url' => '/uploads/test.jpg',
            'is_primary' => 0,
            'sort_order' => 0,
            'file_name' => 'test.jpg',
            'deleted_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('db_product_images')->insert($payload);
        return (int) $this->db->insertID();
    }

    public function test_attach_images_skips_duplicates_and_reports(): void
    {
        $productId = $this->seedProduct(['code' => 'PATT', 'name' => 'Prod for attach']);
        $existingId = $this->seedImage(['product_id' => $productId]);
        $newId = $this->seedImage(['product_id' => null]);

        $result = $this->service->attachImages($productId, [$existingId, $newId, 9999]);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['attached_count']);
        $this->assertSame(1, $result['skipped_count']);
        $this->assertSame(1, $result['missing_count']);
        $this->assertContains($newId, $result['attached_ids']);
        $this->assertContains($existingId, $result['skipped_ids']);
        $this->assertContains(9999, $result['missing_ids']);

        $row = $this->db->table('db_product_images')->where('id', $newId)->get()->getRowArray();
        $this->assertSame($productId, (int) $row['product_id']);
        $this->assertNull($row['deleted_at']);
    }
}
