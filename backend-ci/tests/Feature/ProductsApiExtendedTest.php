<?php

namespace Tests\Feature;

use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Tests\Support\AuthTestTrait;

class ProductsApiExtendedTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTestTrait;

    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetSchema();
        $this->setUpAuthToken();
    }

    private function resetSchema(): void
    {
        $auto = strtoupper($this->db->DBDriver ?? '') === 'SQLITE3' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT';
        $this->db->query('DROP TABLE IF EXISTS product_variants_v2');
        $this->db->query('DROP TABLE IF EXISTS products');
        $this->db->query('DROP TABLE IF EXISTS product_images');
        $this->db->query('DROP TABLE IF EXISTS product_attributes');
        $this->db->query('DROP TABLE IF EXISTS product_attribute_values');

        $this->db->query("CREATE TABLE products (
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

        $this->db->query("CREATE TABLE product_variants_v2 (
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

        $this->db->query("CREATE TABLE product_images (
            id INTEGER PRIMARY KEY {$auto},
            product_id INTEGER,
            variant_id INTEGER,
            image_url TEXT,
            is_primary INTEGER DEFAULT 0,
            sort_order INTEGER DEFAULT 0,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )");

        $this->db->query("CREATE TABLE product_attributes (
            id INTEGER PRIMARY KEY {$auto},
            name TEXT,
            type TEXT,
            code TEXT,
            attribute_key TEXT,
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
            is_visible INTEGER,
            attribute_values TEXT,
            required_at_checkout INTEGER,
            default_value TEXT,
            help_text TEXT,
            icon TEXT,
            tooltip TEXT
        )");

        $this->db->query("CREATE TABLE product_attribute_values (
            id INTEGER PRIMARY KEY {$auto},
            product_id INTEGER,
            variant_id INTEGER,
            attribute_id INTEGER,
            option_id INTEGER,
            value_text TEXT,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
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

        $this->db->table('products')->insert($payload);
        return (int) $this->db->insertID();
    }

    public function test_detailWithVariants(): void
    {
        $id = $this->seedProduct(['code' => 'PV1', 'name' => 'ProdV']);
        $response = $this->withHeaders($this->authHeaders())->get("api/products/{$id}/detail-with-variants");
        $response->assertStatus(200);
        $response->assertJSONPath('data.code', 'PV1');
        $response->assertJSONFragment(['success' => true]);
    }

    public function test_variants_list(): void
    {
        $id = $this->seedProduct(['code' => 'PV2']);
        $response = $this->withHeaders($this->authHeaders())->get("api/products/{$id}/variants");
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
    }

    public function test_checkCode_exists(): void
    {
        $this->seedProduct(['code' => 'DUP']);
        $response = $this->withHeaders($this->authHeaders())
                         ->withBodyFormat('json')->post("api/products/check-code", ['code' => 'DUP']);
        $response->assertStatus(200);
        $response->assertJSONPath('exists', true);
    }

    public function test_checkCode_not_exists(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                         ->withBodyFormat('json')->post("api/products/check-code", ['code' => 'NEW']);
        $response->assertStatus(200);
        $response->assertJSONPath('exists', false);
    }

    public function test_images(): void
    {
        $id = $this->seedProduct(['code' => 'PIMG']);
        $response = $this->withHeaders($this->authHeaders())->get("api/products/{$id}/images");
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
    }

    public function test_attachImages(): void
    {
        $id = $this->seedProduct(['code' => 'PIMG2']);
        $response = $this->withHeaders($this->authHeaders())
                         ->withBodyFormat('json')
                         ->post("api/products/{$id}/images/attach-multiple", ['image_ids' => [999]]);
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
    }

    public function test_analytics(): void
    {
        $id = $this->seedProduct(['code' => 'PANA']);
        $response = $this->withHeaders($this->authHeaders())->get("api/products/{$id}/analytics");
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
    }

    public function test_productAttributeValues(): void
    {
        $id = $this->seedProduct(['code' => 'PATTR']);
        $response = $this->withHeaders($this->authHeaders())->get("api/products/{$id}/attribute-values");
        $response->assertStatus(200);
        $response->assertJSONFragment(['success' => true]);
    }

    public function test_updateProductAttributeValues(): void
    {
         $id = $this->seedProduct(['code' => 'PATTR2']);
         $response = $this->withHeaders($this->authHeaders())
                          ->withBodyFormat('json')
                          ->post("api/products/{$id}/attribute-values", ['attribute_values' => []]);
         $response->assertStatus(200);
         $response->assertJSONFragment(['success' => true]);
    }
}
