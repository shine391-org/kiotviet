<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Services\Products\ProductService;
use App\Repositories\Products\ProductRepository;
use App\Repositories\ProductVariants\ProductVariantRepository;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Services;

class ImageUploadTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = false;
    protected $migrateOnce = false;
    protected $refresh = false;
    protected $namespace = 'App';

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect();
        
        if ($this->db->table('products')->countAllResults() === 0) {
            $this->db->table('products')->insert([
                'id' => 516,
                'code' => 'TEST-001',
                'name' => 'Test Product',
                'slug' => 'test-product',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        if ($this->db->table('product_variants_v2')->countAllResults() === 0) {
            $this->db->table('product_variants_v2')->insert([
                'id' => 51601,
                'product_id' => 516,
                'sku' => 'TEST-001-V1',
                'price' => 100000,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    public function testUploadSingleImage()
    {
        $product = $this->db->table('products')->select('id')->limit(1)->get()->getRowArray();
        $productId = (int)$product['id'];

        $repo = new ProductRepository();
        $rows = [
            [
                'product_id' => $productId,
                'variant_id' => null,
                'image_path' => '/uploads/test.jpg',
                'image_url' => '/uploads/test.jpg',
                'is_primary' => 0,
                'sort_order' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'file_name' => 'test.jpg'
            ]
        ];

        try {
            $result = $repo->insertImages($rows);
            $this->assertNotEmpty($result);
            $this->assertArrayHasKey('id', $result[0]);
            echo "\n✓ Image insert successful for Product ID: $productId\n";
        } catch (\Exception $e) {
            $this->fail("Image insert failed: " . $e->getMessage());
        }
    }

    public function testAttributeValuesQuery()
    {
        $variant = $this->db->table('product_variants_v2')->select('id')->limit(1)->get()->getRowArray();
        $variantId = (int)$variant['id'];

        $repo = new ProductVariantRepository();
        
        try {
            $attributes = $repo->attributeValues($variantId);
            $this->assertIsArray($attributes);
            echo "\n✓ attributeValues query successful for Variant ID: $variantId\n";
        } catch (\Exception $e) {
            $this->fail("attributeValues query failed: " . $e->getMessage());
        }
    }
    
    public function testProductUsedAttributeOptionsQuery()
    {
        $product = $this->db->table('products')->select('id')->limit(1)->get()->getRowArray();
        $productId = (int)$product['id'];
        
        $repo = new ProductRepository();
        
        try {
            $options = $repo->usedAttributeOptions($productId);
            $this->assertIsArray($options);
            echo "\n✓ usedAttributeOptions query successful for Product ID: $productId\n";
        } catch (\Exception $e) {
            $this->fail("usedAttributeOptions query failed: " . $e->getMessage());
        }
    }

    public function testVariantUploadMultiple()
    {
        $variant = $this->db->table('product_variants_v2')->select('id, product_id')->limit(1)->get()->getRowArray();
        $variantId = (int)$variant['id'];
        $productId = (int)$variant['product_id'];

        $repo = new ProductRepository();
        $rows = [
            [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'image_path' => '/uploads/variants/test-var.jpg',
                'image_url' => '/uploads/variants/test-var.jpg',
                'is_primary' => 0,
                'sort_order' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'file_name' => 'test-var.jpg'
            ]
        ];
        
        try {
            $inserted = $repo->insertImages($rows);
            $this->assertNotEmpty($inserted);
            $this->assertEquals($variantId, $inserted[0]['variant_id']);
            echo "\n✓ Variant image insert successful for Variant ID: $variantId\n";
        } catch (\Exception $e) {
            $this->fail("Variant image insert failed: " . $e->getMessage());
        }
    }
}
