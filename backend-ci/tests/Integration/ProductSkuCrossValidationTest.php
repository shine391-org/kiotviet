<?php

namespace Tests\Integration;

use App\Services\Products\ProductService;
use App\Services\ProductVariants\ProductVariantService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use InvalidArgumentException;

/**
 * End-to-end style validation tests for code/SKU cross-checks.
 * Uses SQLite in-memory (tests group) to simulate real flows.
 */
class ProductSkuCrossValidationTest extends CIUnitTestCase
{
    private ProductService $productService;
    private ProductVariantService $variantService;
    private \CodeIgniter\Database\BaseConnection $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetSchema();
        $this->productService = new ProductService();
        $this->variantService = new ProductVariantService();
    }

    public function test_product_code_cannot_duplicate_variant_sku(): void
    {
        $p1 = $this->seedProduct(['code' => 'P001']);
        $this->seedVariant($p1, 'SKU-111');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mã sản phẩm đã tồn tại trong danh sách phiên bản');

        $this->productService->create(['code' => 'SKU-111', 'name' => 'Clash product']);
    }

    public function test_variant_sku_cannot_duplicate_product_code(): void
    {
        $p1 = $this->seedProduct(['code' => 'PABC']);
        $p2 = $this->seedProduct(['code' => 'PDEF']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SKU đã tồn tại trong danh sách sản phẩm');

        $this->variantService->create($p2, ['sku' => 'PABC', 'variant_name' => 'dup']);
    }

    public function test_variant_sku_cannot_duplicate_other_variant(): void
    {
        $p1 = $this->seedProduct(['code' => 'PX']);
        $this->seedVariant($p1, 'DUP-SKU');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SKU đã tồn tại trong danh sách phiên bản');

        $this->variantService->create($p1, ['sku' => 'DUP-SKU', 'variant_name' => 'dup2']);
    }

    public function test_check_code_reports_variant_collision(): void
    {
        $p1 = $this->seedProduct(['code' => 'PZX']);
        $this->seedVariant($p1, 'SV-01');

        $result = $this->productService->checkCode('SV-01');

        $this->assertTrue($result['exists']);
        $this->assertFalse($result['exists_in_products']);
        $this->assertTrue($result['exists_in_variants']);
    }

    private function resetSchema(): void
    {
        $this->db->query('DROP TABLE IF EXISTS db_product_variants_v2');
        $this->db->query('DROP TABLE IF EXISTS db_products');

        $this->db->query('CREATE TABLE db_products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT,
            name TEXT,
            product_type TEXT,
            barcode TEXT,
            status TEXT,
            selling_price REAL,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
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
            'code' => 'P' . random_int(1000, 9999),
            'name' => 'Sample',
            'product_type' => 'goods',
            'status' => 'active',
            'selling_price' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ], $data);

        $this->db->table('db_products')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function seedVariant(int $productId, string $sku): int
    {
        $this->db->table('db_product_variants_v2')->insert([
            'product_id' => $productId,
            'variant_name' => $sku,
            'variant_signature' => $sku,
            'sku' => $sku,
            'price' => 10,
            'cost_price' => 5,
            'stock_quantity' => 1,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ]);
        return (int) $this->db->insertID();
    }
}

