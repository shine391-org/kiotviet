<?php

namespace Tests\Services;

use App\Repositories\Products\ProductRepository; // Added for seeding products
use App\Services\ProductVariants\ProductVariantService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

class ProductVariantServiceTest extends CIUnitTestCase
{
    private ProductVariantService $service;
    protected $db;
    protected ProductRepository $productRepo; // Added for seeding products

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetSchema();
        $this->productRepo = new ProductRepository(null, null, null, $this->db);
        $this->service = new ProductVariantService(null, null, $this->productRepo);
    }

    public function test_create_variant_respects_route_product_id(): void
    {
        // Seed two products
        $productIdA = $this->seedProduct(['code' => 'PA', 'name' => 'Product A']);
        $productIdB = $this->seedProduct(['code' => 'PB', 'name' => 'Product B']);

        // Attempt to create a variant for Product A, but pass Product B's ID in the data payload
        $data = [
            'product_id' => $productIdB, // This should be ignored
            'variant_name' => 'Variant for Product A',
            'sku' => 'SKU_PA_V1',
            'price' => 100.00,
            'cost_price' => 50.00,
            'stock_quantity' => 10,
        ];

        $result = $this->service->create($productIdA, $data);

        // Assert that the variant was created successfully
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['data']['id']);

        // Retrieve the created variant from the database
        $createdVariant = $this->db->table('db_product_variants_v2')
                                   ->where('id', $result['data']['id'])
                                   ->get()
                                   ->getRowArray();

        // Assert that the created variant is indeed associated with productIdA (from the route)
        $this->assertNotNull($createdVariant);
        $this->assertSame((string)$productIdA, (string)$createdVariant['product_id'], 'Product ID from route should take precedence');
    }

    public function test_create_variant_fails_when_sku_matches_product_code(): void
    {
        $productId = $this->seedProduct(['code' => 'CP100', 'name' => 'Product']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SKU đã tồn tại trong danh sách sản phẩm');

        $this->service->create($productId, ['sku' => 'CP100']);
    }

    public function test_update_variant_blocks_duplicate_sku(): void
    {
        $productId = $this->seedProduct(['code' => 'CP200', 'name' => 'Product']);
        $this->seedVariant($productId, 'SKU-ONE');
        $secondVariantId = $this->seedVariant($productId, 'SKU-TWO');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SKU đã tồn tại trong danh sách phiên bản');

        $this->service->update($secondVariantId, ['sku' => 'SKU-ONE']);
    }

    // Helper methods (copied and adapted from ProductServiceTest for consistency)
    private function resetSchema(): void
    {
        $this->db->query('DROP TABLE IF EXISTS db_product_variants_v2');
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

    private function seedVariant(int $productId, string $sku): int
    {
        $this->db->table('db_product_variants_v2')->insert([
            'product_id' => $productId,
            'variant_name' => $sku,
            'variant_signature' => $sku,
            'sku' => $sku,
            'barcode' => null,
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
