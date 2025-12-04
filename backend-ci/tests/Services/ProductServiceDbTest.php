<?php

namespace Tests\Services;

use App\Repositories\Products\ProductRepository;
use App\Services\PriceLists\PriceCalculatorService;
use App\Services\Products\ProductService;
use App\Validators\ProductValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\ProductSchemaTrait;

/**
 * @agent-test: ProductService (DB)
 * @agent-pattern: Service test with DevDatabaseTrait + ProductSchemaTrait
 */
class ProductServiceDbTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use ProductSchemaTrait;

    private ProductService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetProductSchema();

        $repo = new ProductRepository(null, null, null, $this->db);
        $validator = new ProductValidator(null, $repo, null);
        $pricing = new class extends PriceCalculatorService {
            public function __construct() {}
            public function getProductPriceByListId(int $priceListId, int $productId, ?int $variantId = null, float $quantity = 1.0): array
            {
                return [
                    'base_price' => 100,
                    'final_price' => 90,
                    'applied_price_list_id' => $priceListId,
                    'applied_price_list_name' => 'Test',
                    'price_list_type' => 'manual',
                    'quantity' => $quantity,
                    'line_total' => 90 * $quantity,
                ];
            }
        };

        $this->service = new ProductService($repo, $validator, $pricing);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function testCreateAndGet(): void
    {
        $create = $this->service->create(['code' => 'PR-1', 'name' => 'Product 1']);

        $this->assertTrue($create['success']);
        $id = $create['data']['id'];

        $get = $this->service->get($id, false, false);
        $this->assertTrue($get['success']);
        $this->assertSame('PR-1', $get['data']['code']);
    }

    public function testListReturnsVariantsWhenRequested(): void
    {
        $p = $this->createProduct(['code' => 'PR-2', 'name' => 'P2']);
        $this->createVariant($p['id'], ['sku' => 'SKU-2']);

        $result = $this->service->list(['include_variants' => true, 'page' => 1, 'limit' => 10]);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
        $this->assertNotEmpty($result['data'][0]['variants']);
    }

    public function testDeleteSoftRemovesProduct(): void
    {
        $p = $this->createProduct(['code' => 'PR-DEL', 'name' => 'Delete me']);
        $this->service->delete($p['id']);

        $this->assertNull($this->db->table('products')->where('id', $p['id'])->where('deleted_at', null)->get()->getRowArray());
    }

    public function testCheckCodeDetectsVariantCollision(): void
    {
        $p = $this->createProduct(['code' => 'PR-3', 'name' => 'Base']);
        $this->createVariant($p['id'], ['sku' => 'DUP-CODE']);

        $res = $this->service->checkCode('DUP-CODE');

        $this->assertTrue($res['exists']);
        $this->assertTrue($res['exists_in_variants']);
    }

    private function createProduct(array $overrides = []): array
    {
        $now = date('Y-m-d H:i:s');
        $data = array_merge([
            'product_type' => 'goods',
            'code' => 'CODE-' . uniqid(),
            'name' => 'Product ' . uniqid(),
            'slug' => 'slug-' . uniqid(),
            'status' => 'active',
            'selling_price' => 0,
            'purchase_price' => 0,
            'stock_quantity' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);
        $this->db->table('products')->insert($data);
        $data['id'] = (int) $this->db->insertID();
        return $data;
    }

    private function createVariant(int $productId, array $overrides = []): int
    {
        $now = date('Y-m-d H:i:s');
        $data = array_merge([
            'product_id' => $productId,
            'variant_name' => 'Variant ' . uniqid(),
            'variant_signature' => 'sig-' . uniqid(),
            'sku' => 'SKU-' . uniqid(),
            'price' => 50,
            'stock_quantity' => 0,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);
        $this->db->table('product_variants_v2')->insert($data);
        return (int) $this->db->insertID();
    }
}
