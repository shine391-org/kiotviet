<?php

namespace Tests\Repositories;

use App\Repositories\Products\ProductRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\ProductSchemaTrait;

class ProductRepositoryTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use ProductSchemaTrait;

    private ProductRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetSchema();
        $this->repo = new ProductRepository(null, null, null, $this->db);
    }

    public function testFindAllWithSearch(): void
    {
        $this->insertProduct(['code' => 'AO01', 'name' => 'ao so mi']);
        $this->insertProduct(['code' => 'QU01', 'name' => 'quan jean']);

        $result = $this->repo->findAll(['search' => 'ao', 'page' => 1, 'limit' => 10]);

        $this->assertCount(1, $result);
        $this->assertSame('AO01', $result[0]['code']);
    }

    public function testSoftDelete(): void
    {
        $id = $this->insertProduct(['code' => 'DEL01', 'name' => 'delete item']);

        $this->assertTrue($this->repo->delete($id));

        $this->assertNull($this->repo->findById($id));
        $this->assertSame(0, $this->repo->count(['page' => 1, 'limit' => 10]));
    }

    public function testCodeExists(): void
    {
        $id = $this->insertProduct(['code' => 'EX01', 'name' => 'exists']);

        $this->assertTrue($this->repo->codeExists('EX01'));
        $this->assertFalse($this->repo->codeExists('EX01', $id));
        $this->assertFalse($this->repo->codeExists('NEW')); // not exists
    }


    private function insertProduct(array $data): int
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
}
