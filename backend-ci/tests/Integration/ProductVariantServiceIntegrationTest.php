<?php

namespace Tests\Integration;

use App\Services\ProductVariants\ProductVariantService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\Database\ProductSchemaTrait;

class ProductVariantServiceIntegrationTest extends CIUnitTestCase
{
    use ProductSchemaTrait;

    protected ProductVariantService $variantService;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetSchema();
        $this->variantService = new ProductVariantService();
    }

    public function test_create_variant_success(): void
    {
        $productId = $this->seedProduct();

        $data = [
            'sku' => 'VAR-001',
            'variant_name' => 'Variant 1',
            'price' => 50000,
            'stock_quantity' => 10
        ];

        $result = $this->variantService->create($productId, $data);

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        $this->assertEquals('VAR-001', $result['data']['sku']);

        $inDb = $this->db->table('db_product_variants_v2')->where('sku', 'VAR-001')->get()->getRowArray();
        $this->assertNotNull($inDb);
        $this->assertEquals($productId, $inDb['product_id']);
    }

    public function test_update_variant_success(): void
    {
        $productId = $this->seedProduct();
        $variantId = $this->seedVariant($productId, ['sku' => 'VAR-OLD', 'variant_name' => 'Old Name']);

        $result = $this->variantService->update($variantId, ['variant_name' => 'New Name']);

        $this->assertTrue($result['success']);
        $inDb = $this->db->table('db_product_variants_v2')->where('id', $variantId)->get()->getRowArray();
        $this->assertEquals('New Name', $inDb['variant_name']);
    }

    public function test_delete_variant_soft(): void
    {
        $productId = $this->seedProduct();
        $variantId = $this->seedVariant($productId, ['sku' => 'VAR-DEL']);

        $result = $this->variantService->delete($variantId);

        $this->assertTrue($result['success']);
        $inDb = $this->db->table('db_product_variants_v2')->where('id', $variantId)->get()->getRowArray();
        $this->assertNotNull($inDb['deleted_at']);
    }

    private function seedProduct(): int
    {
        $this->db->table('db_products')->insert([
            'code' => 'P' . random_int(1000, 9999),
            'name' => 'Parent Product',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insertID();
    }

    private function seedVariant(int $productId, array $data): int
    {
        $payload = array_merge([
            'product_id' => $productId,
            'variant_name' => 'Variant',
            'sku' => 'SKU-' . random_int(1000, 9999),
            'price' => 100,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ], $data);

        $this->db->table('db_product_variants_v2')->insert($payload);
        return (int) $this->db->insertID();
    }
}
