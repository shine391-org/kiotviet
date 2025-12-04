<?php

namespace Tests\Repositories;

use App\Repositories\Products\ProductRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\ProductSchemaTrait;

/**
 * @agent-test: ProductRepository
 * @agent-pattern: Repository test with DevDatabaseTrait + ProductSchemaTrait
 */
class ProductRepositoryTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use ProductSchemaTrait;

    private ProductRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetProductSchema();
        $this->repo = new ProductRepository(null, null, null, $this->db);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function testFindAllAppliesFilters(): void
    {
        $this->createProduct(['code' => 'A1', 'name' => 'Alpha', 'status' => 'active', 'product_type' => 'goods']);
        $this->createProduct(['code' => 'B2', 'name' => 'Beta', 'status' => 'inactive', 'product_type' => 'service']);

        $result = $this->repo->findAll(['search' => 'A', 'status' => 'active', 'page' => 1, 'limit' => 10]);

        $this->assertCount(1, $result);
        $this->assertSame('A1', $result[0]['code']);
        $this->assertSame(1, $this->repo->count(['search' => 'A', 'status' => 'active']));
    }

    public function testCodeExistsRespectsExcludeId(): void
    {
        $p1 = $this->createProduct(['code' => 'DUP', 'name' => 'Dup']);

        $this->assertTrue($this->repo->codeExists('DUP'));
        $this->assertFalse($this->repo->codeExists('DUP', $p1['id']));
    }

    public function testVariantMapReturnsImageUrl(): void
    {
        $product = $this->createProduct(['code' => 'P1', 'name' => 'P1']);
        $variantId = $this->createVariant($product['id'], ['sku' => 'SKU-1']);
        $this->createImage(['variant_id' => $variantId, 'image_url' => '/img/var1.jpg', 'sort_order' => 1]);

        $map = $this->repo->variantMap([$product['id']]);

        $this->assertArrayHasKey($product['id'], $map);
        $this->assertSame('/img/var1.jpg', $map[$product['id']][0]['image_url']);
    }

    public function testAttachImagesSkipsExistingAndUpdatesAttachable(): void
    {
        $product = $this->createProduct(['code' => 'P1', 'name' => 'P1']);
        $img1 = $this->createImage(['product_id' => $product['id'], 'image_url' => '/img/old.jpg']);
        $img2 = $this->createImage(['product_id' => null, 'image_url' => '/img/new.jpg']);

        $result = $this->repo->attachImages($product['id'], [$img1, $img2, 999]);

        $this->assertSame([$img2], $result['attached_ids']);
        $this->assertSame([$img1], $result['skipped_ids']);
        $this->assertSame([999], $result['missing_ids']);

        $row = $this->db->table('product_images')->where('id', $img2)->get()->getRowArray();
        $this->assertSame($product['id'], (int) $row['product_id']);
        $this->assertNull($row['deleted_at']);
    }

    public function testSetPrimaryImageUpdatesFlags(): void
    {
        $product = $this->createProduct(['code' => 'P1', 'name' => 'P1']);
        $img1 = $this->createImage(['product_id' => $product['id'], 'image_url' => '/img/1.jpg', 'is_primary' => 1, 'sort_order' => 0]);
        $img2 = $this->createImage(['product_id' => $product['id'], 'image_url' => '/img/2.jpg', 'is_primary' => 0, 'sort_order' => 1]);

        $this->repo->setPrimaryImage($img2);

        $first = $this->db->table('product_images')->where('id', $img1)->get()->getRowArray();
        $second = $this->db->table('product_images')->where('id', $img2)->get()->getRowArray();
        $this->assertSame('0', (string) $first['is_primary']);
        $this->assertSame('1', (string) $second['is_primary']);
    }

    public function testImagesReturnsNonDeletedOrdered(): void
    {
        $product = $this->createProduct(['code' => 'P1', 'name' => 'P1']);
        $this->createImage(['product_id' => $product['id'], 'image_url' => '/img/1.jpg', 'sort_order' => 2]);
        $this->createImage(['product_id' => $product['id'], 'image_url' => '/img/2.jpg', 'sort_order' => 1, 'deleted_at' => date('Y-m-d H:i:s')]);
        $this->createImage(['product_id' => $product['id'], 'image_url' => '/img/3.jpg', 'sort_order' => 0]);

        $images = $this->repo->images($product['id']);

        $this->assertCount(2, $images);
        $this->assertSame('/img/3.jpg', $images[0]['image_url']);
        $this->assertSame('/img/1.jpg', $images[1]['image_url']);
    }

    public function testDeleteSoftSetsDeletedAt(): void
    {
        $product = $this->createProduct(['code' => 'P1', 'name' => 'P1']);
        $imgId = $this->createImage(['product_id' => $product['id'], 'image_url' => '/img/1.jpg']);

        $this->repo->deleteImage($imgId, false);

        $row = $this->db->table('product_images')->where('id', $imgId)->get()->getRowArray();
        $this->assertNotNull($row['deleted_at']);
    }

    private function createProduct(array $overrides = []): array
    {
        $now = date('Y-m-d H:i:s');
        $data = array_merge([
            'product_type' => 'goods',
            'code' => 'CODE-' . uniqid(),
            'barcode' => null,
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
            'variant_name' => 'VN-' . uniqid(),
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

    private function createImage(array $overrides = []): int
    {
        $now = date('Y-m-d H:i:s');
        $data = array_merge([
            'product_id' => null,
            'variant_id' => null,
            'image_path' => null,
            'image_url' => '/img/' . uniqid() . '.jpg',
            'is_primary' => 0,
            'sort_order' => 0,
            'file_name' => null,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ], $overrides);
        $this->db->table('product_images')->insert($data);
        return (int) $this->db->insertID();
    }
}
