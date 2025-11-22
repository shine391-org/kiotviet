<?php

namespace Tests\Services;

use App\Services\Attributes\AttributeService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use InvalidArgumentException;
use RuntimeException;

class AttributeServiceTest extends CIUnitTestCase
{
    private AttributeService $service;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetSchema();
        $repo = new \App\Repositories\Attributes\AttributeRepository(null, 'tests'); // Pass 'tests' group
        $this->service = new AttributeService($repo); // Pass the configured repository
    }

    public function test_list_filters_by_type_and_status(): void
    {
        $this->seedAttribute(['name' => 'Color', 'type' => 'select', 'status' => 'active']);
        $this->seedAttribute(['name' => 'Size', 'type' => 'text', 'status' => 'inactive']);

        $result = $this->service->list(['type' => 'select', 'status' => 'active']);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
        $this->assertSame('Color', $result['data'][0]['name']);
    }

    public function test_create_attribute_generates_slug_and_key(): void
    {
        $result = $this->service->create(['name' => 'Material']);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['data']['id']);
        $row = $this->db->table('db_product_attributes')->where('id', $result['data']['id'])->get()->getRowArray();
        $this->assertNotEmpty($row['slug']);
        $this->assertNotEmpty($row['attribute_key']);
    }

    public function test_update_attribute_changes_fields(): void
    {
        $id = $this->seedAttribute(['name' => 'Attr', 'status' => 'active']);

        $updated = $this->service->update($id, ['status' => 'inactive']);

        $this->assertTrue($updated['success']);
        $row = $this->db->table('db_product_attributes')->where('id', $id)->get()->getRowArray();
        $this->assertSame('inactive', $row['status']);
    }

    public function test_delete_attribute_marks_deleted(): void
    {
        $id = $this->seedAttribute(['name' => 'To delete']);
        $this->service->delete($id);
        $row = $this->db->table('db_product_attributes')->where('id', $id)->get()->getRowArray();
        $this->assertNotNull($row['deleted_at']);
    }

    public function test_create_option_and_list(): void
    {
        $attrId = $this->seedAttribute(['name' => 'Color']);
        $this->service->createOption($attrId, ['option_name' => 'Red']);

        $options = $this->service->options($attrId);
        $this->assertCount(1, $options['data']);
        $this->assertSame('Red', $options['data'][0]['option_name']);
    }

    public function test_update_option(): void
    {
        $attrId = $this->seedAttribute(['name' => 'Color']);
        $optionId = $this->seedOption($attrId, ['option_name' => 'Old']);

        $this->service->updateOption($optionId, ['option_name' => 'New']);
        $row = $this->db->table('db_product_attribute_options')->where('id', $optionId)->get()->getRowArray();
        $this->assertSame('New', $row['option_name']);
    }

    public function test_delete_option(): void
    {
        $attrId = $this->seedAttribute(['name' => 'Color']);
        $optionId = $this->seedOption($attrId, ['option_name' => 'Remove']);

        $this->service->deleteOption($optionId);
        $row = $this->db->table('db_product_attribute_options')->where('id', $optionId)->get()->getRowArray();
        $this->assertNull($row);
    }

    public function test_create_value_requires_attribute(): void
    {
        $attrId = $this->seedAttribute(['name' => 'Color']);
        $result = $this->service->createValue(['attribute_id' => $attrId, 'value_text' => 'Blue']);
        $this->assertTrue($result['success']);
        $row = $this->db->table('db_product_attribute_values')->where('id', $result['data']['id'])->get()->getRowArray();
        $this->assertSame('Blue', $row['value_text']);
    }

    public function test_products_by_option_returns_mappings(): void
    {
        $attrId = $this->seedAttribute(['name' => 'Color']);
        $optionId = $this->seedOption($attrId, ['option_name' => 'Green']);
        $this->db->table('db_product_attribute_values')->insert([
            'product_id' => 10,
            'variant_id' => 0,
            'attribute_id' => $attrId,
            'option_id' => $optionId,
            'value_text' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ]);

        $result = $this->service->productsByOption($optionId);
        $this->assertCount(1, $result['data']);
        $this->assertSame(10, (int) $result['data'][0]['product_id']);
    }

    public function test_show_throws_when_missing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service->show(999);
    }

    public function test_validation_errors(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->create([]);
    }

    public function test_create_attribute_with_only_name_sets_defaults(): void
    {
        $result = $this->service->create(['name' => 'New Attribute']);

        $this->assertTrue($result['success']);
        $id = $result['data']['id'];

        $row = $this->db->table('db_product_attributes')->where('id', $id)->get()->getRowArray();

        $this->assertNotNull($row);
        $this->assertSame('select', $row['type']);
        $this->assertSame('0', (string)$row['is_required']);
        $this->assertSame('1', (string)$row['is_filterable']);
        $this->assertSame('1', (string)$row['is_visible']);
        $this->assertSame('0', (string)$row['sort_order']);
        $this->assertSame('active', $row['status']);
        $this->assertNotEmpty($row['slug']);
        $this->assertNotEmpty($row['attribute_key']);
    }

    private function resetSchema(): void
    {
        $this->db->query('DROP TABLE IF EXISTS db_product_attribute_values');
        $this->db->query('DROP TABLE IF EXISTS db_product_attribute_options');
        $this->db->query('DROP TABLE IF EXISTS db_product_attributes');

        $this->db->query('CREATE TABLE db_product_attributes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            slug TEXT,
            attribute_key TEXT,
            type TEXT,
            is_required INTEGER,
            is_filterable INTEGER,
            is_visible INTEGER,
            sort_order INTEGER,
            status TEXT,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )');

        $this->db->query('CREATE TABLE db_product_attribute_options (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            attribute_id INTEGER,
            option_name TEXT,
            option_value TEXT,
            color_code TEXT,
            image_url TEXT,
            sort_order INTEGER,
            status TEXT,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
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

    private function seedAttribute(array $data): int
    {
        $payload = array_merge([
            'name' => 'Attr',
            'slug' => 'attr',
            'attribute_key' => uniqid('attr_'),
            'type' => 'select',
            'is_required' => 0,
            'is_filterable' => 1,
            'is_visible' => 1,
            'sort_order' => 0,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ], $data);
        $this->db->table('db_product_attributes')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function seedOption(int $attributeId, array $data): int
    {
        $payload = array_merge([
            'attribute_id' => $attributeId,
            'option_name' => 'Default',
            'option_value' => null,
            'color_code' => null,
            'image_url' => null,
            'sort_order' => 0,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ], $data);
        $this->db->table('db_product_attribute_options')->insert($payload);
        return (int) $this->db->insertID();
    }
}
