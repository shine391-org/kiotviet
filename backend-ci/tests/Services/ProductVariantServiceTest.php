<?php

namespace Tests\Services;

use App\Repositories\Products\ProductRepository;
use App\Services\ProductVariants\ProductVariantService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use CodeIgniter\HTTP\Files\UploadedFile;

class ProductVariantServiceTest extends CIUnitTestCase
{
    private ProductVariantService $service;
    protected $db;
    protected ProductRepository $productRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetSchema();
        $this->productRepo = new ProductRepository(null, null, null, $this->db);
        $this->service = new ProductVariantService(null, null, $this->productRepo);
    }

    private function resetSchema(): void
    {
        $this->db->query('DROP TABLE IF EXISTS db_product_variants_v2');
        $this->db->query('DROP TABLE IF EXISTS db_products');
        $this->db->query('DROP TABLE IF EXISTS db_product_images');
        $this->db->query('DROP TABLE IF EXISTS db_product_attributes');
        $this->db->query('DROP TABLE IF EXISTS db_product_attribute_values');

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

        $this->db->query('CREATE TABLE db_product_images (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER,
            variant_id INTEGER,
            image_url TEXT,
            is_primary INTEGER DEFAULT 0,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )');

        $this->db->query('CREATE TABLE db_product_attributes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            type TEXT,
            code TEXT,
            status TEXT,
            is_required INTEGER,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT,
            slug TEXT,
            sort_order INTEGER,
            group_name TEXT,
            is_filterable INTEGER,
            parent_id INTEGER,
            level INTEGER,
            description TEXT,
            unit TEXT,
            options TEXT,
            display_type TEXT,
            is_searchable INTEGER,
            is_used_for_variations INTEGER,
            is_highlight INTEGER,
            meta TEXT,
            is_system INTEGER,
            is_default INTEGER,
            position INTEGER,
            created_by INTEGER,
            updated_by INTEGER,
            filterable INTEGER,
            comparable INTEGER,
            visibility TEXT,
            required_at_checkout INTEGER,
            default_value TEXT,
            help_text TEXT,
            icon TEXT,
            tooltip TEXT
        )');

        $this->db->query('CREATE TABLE db_product_attribute_values (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER,
            variant_id INTEGER,
            attribute_id INTEGER,
            option_id INTEGER,
            value_text TEXT,
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

    public function test_show_returns_variant(): void
    {
        $pid = $this->seedProduct(['code' => 'P1']);
        $vid = $this->seedVariant($pid, 'SKU1');

        $result = $this->service->show($vid);
        $this->assertTrue($result['success']);
        $this->assertEquals($vid, $result['data']['id']);
    }

    public function test_show_throws_exception_if_not_found(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->service->show(999);
    }

    public function test_create_variant_respects_route_product_id(): void
    {
        $productIdA = $this->seedProduct(['code' => 'PA', 'name' => 'Product A']);
        $productIdB = $this->seedProduct(['code' => 'PB', 'name' => 'Product B']);

        $data = [
            'product_id' => $productIdB,
            'variant_name' => 'Variant for Product A',
            'sku' => 'SKU_PA_V1',
            'price' => 100.00,
            'cost_price' => 50.00,
            'stock_quantity' => 10,
        ];

        $result = $this->service->create($productIdA, $data);

        $this->assertTrue($result['success']);
        $createdVariant = $this->db->table('db_product_variants_v2')
                                   ->where('id', $result['data']['id'])
                                   ->get()
                                   ->getRowArray();
        $this->assertSame((string)$productIdA, (string)$createdVariant['product_id']);
    }

    public function test_create_variant_fails_when_sku_matches_product_code(): void
    {
        $productId = $this->seedProduct(['code' => 'CP100', 'name' => 'Product']);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->create($productId, ['sku' => 'CP100']);
    }

    public function test_update_variant_success(): void
    {
        $pid = $this->seedProduct(['code' => 'P1']);
        $vid = $this->seedVariant($pid, 'SKU1');

        $result = $this->service->update($vid, ['variant_name' => 'New Name']);
        $this->assertTrue($result['success']);

        $row = $this->db->table('db_product_variants_v2')->where('id', $vid)->get()->getRowArray();
        $this->assertEquals('New Name', $row['variant_name']);
    }

    public function test_update_variant_blocks_duplicate_sku(): void
    {
        $productId = $this->seedProduct(['code' => 'CP200', 'name' => 'Product']);
        $this->seedVariant($productId, 'SKU-ONE');
        $secondVariantId = $this->seedVariant($productId, 'SKU-TWO');

        $this->expectException(\InvalidArgumentException::class);
        $this->service->update($secondVariantId, ['sku' => 'SKU-ONE']);
    }

    public function test_delete_soft_deletes(): void
    {
        $pid = $this->seedProduct(['code' => 'P1']);
        $vid = $this->seedVariant($pid, 'SKU1');

        $result = $this->service->delete($vid);
        $this->assertTrue($result['success']);

        $row = $this->db->table('db_product_variants_v2')->where('id', $vid)->get()->getRowArray();
        $this->assertNotNull($row['deleted_at']);
    }

    public function test_restore_variant(): void
    {
        $pid = $this->seedProduct(['code' => 'P1']);
        $vid = $this->seedVariant($pid, 'SKU1');
        $this->service->delete($vid);

        $result = $this->service->restore($vid);
        $this->assertTrue($result['success']);

        $row = $this->db->table('db_product_variants_v2')->where('id', $vid)->get()->getRowArray();
        $this->assertNull($row['deleted_at']);
    }

    public function test_hard_delete_variant(): void
    {
        $pid = $this->seedProduct(['code' => 'P1']);
        $vid = $this->seedVariant($pid, 'SKU1');

        $result = $this->service->hardDelete($vid);
        $this->assertTrue($result['success']);

        $row = $this->db->table('db_product_variants_v2')->where('id', $vid)->get()->getRowArray();
        $this->assertNull($row);
    }

    public function test_uploadMultiple(): void
    {
        $productId = $this->seedProduct(['code' => 'P1']);
        $variantId = $this->seedVariant($productId, 'SKU1');

        $file = $this->createMock(UploadedFile::class);
        $file->method('isValid')->willReturn(true);
        $file->method('getRandomName')->willReturn('random.jpg');
        $file->expects($this->once())->method('move');

        $result = $this->service->uploadMultiple($variantId, [$file]);
        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']['files']);
    }

    public function test_uploadMultiple_fails_if_no_valid_files(): void
    {
        $productId = $this->seedProduct(['code' => 'P1']);
        $variantId = $this->seedVariant($productId, 'SKU1');

        $this->expectException(\InvalidArgumentException::class);
        $this->service->uploadMultiple($variantId, []);
    }

    public function test_attachImages(): void
    {
        $pid = $this->seedProduct(['code' => 'P1']);
        $vid = $this->seedVariant($pid, 'SKU1');

        // Seed images
        $this->db->table('db_product_images')->insert([
            'product_id' => $pid, 'variant_id' => null, 'image_url' => 'img1.jpg', 'created_at' => date('Y-m-d')
        ]);
        $img1 = $this->db->insertID();

        $this->db->table('db_product_images')->insert([
            'product_id' => $pid, 'variant_id' => $vid, 'image_url' => 'img2.jpg', 'created_at' => date('Y-m-d')
        ]);
        $img2 = $this->db->insertID();

        $result = $this->service->attachImages($vid, [$img1, $img2, 999]);

        $this->assertTrue($result['success']);
        $this->assertContains($img1, $result['attached_ids']);
        $this->assertContains($img2, $result['skipped_ids']); // Already attached
        $this->assertContains(999, $result['missing_ids']);
    }

    public function test_attributeValues_and_sync(): void
    {
        $pid = $this->seedProduct(['code' => 'P1']);
        $vid = $this->seedVariant($pid, 'SKU1');

        // Seed Attribute
        $this->db->table('db_product_attributes')->insert([
            'name' => 'Color', 'code' => 'color', 'type' => 'select', 'created_at' => date('Y-m-d')
        ]);
        $attrId = $this->db->insertID();

        // Sync
        $values = [
            ['attribute_id' => $attrId, 'value_text' => 'Red', 'product_id' => $pid]
        ];
        $syncResult = $this->service->syncAttributeValues($vid, $values);
        $this->assertTrue($syncResult['success']);
        $this->assertCount(1, $syncResult['data']);

        // Get
        $getResult = $this->service->attributeValues($vid);
        $this->assertTrue($getResult['success']);
        $this->assertCount(1, $getResult['data']);
        $this->assertEquals('Color', $getResult['data'][0]['attribute_name']);

        // Remove
        $removeResult = $this->service->removeAttributeFromVariant($vid, $attrId);
        $this->assertTrue($removeResult['success']);

        $getResultEmpty = $this->service->attributeValues($vid);
        $this->assertCount(0, $getResultEmpty['data']);
    }

    public function test_deletedList(): void
    {
        $pid = $this->seedProduct(['code' => 'P1']);
        $vid1 = $this->seedVariant($pid, 'SKU1');
        $vid2 = $this->seedVariant($pid, 'SKU2');

        $this->service->delete($vid1);

        $result = $this->service->deletedList($pid);
        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']['variants']);
        $this->assertEquals($vid1, $result['data']['variants'][0]['id']);
    }
}
