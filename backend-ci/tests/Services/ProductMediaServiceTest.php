<?php

namespace Tests\Services;

use App\Services\ProductMedia\ProductMediaService;
use App\Repositories\ProductMedia\ProductMediaRepository;
use App\Validators\ProductMediaValidator;
use App\Validators\ProductMediaDateValidator;
use App\Validators\ProductMediaSearchValidator;
use App\Transformers\ProductMediaTransformer;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

class ProductMediaServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private ProductMediaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $repo = new ProductMediaRepository($this->db);
        $this->service = new ProductMediaService(
            $repo,
            new ProductMediaValidator(),
            new ProductMediaDateValidator(),
            new ProductMediaSearchValidator(),
            new ProductMediaTransformer()
        );
    }

    public function test_library_returns_images(): void
    {
        $this->db->table('product_images')->insert([
            'image_url' => 'img1.jpg',
            'created_at' => '2023-01-01 10:00:00',
            'is_primary' => 0,
            'sort_order' => 1
        ]);

        $result = $this->service->library(['limit' => 10, 'offset' => 0, 'entity_id' => 1]);
        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals(1, $result['pagination']['total']);
        $this->assertFalse($result['data'][0]['is_attached']); // Not attached
    }

    public function test_library_marks_attached(): void
    {
        $this->db->table('product_images')->insert([
            'image_url' => 'img1.jpg',
            'product_id' => 1,
            'created_at' => '2023-01-01 10:00:00'
        ]);

        $result = $this->service->library(['entity_id' => 1]);
        $this->assertTrue($result['data'][0]['is_attached']);
    }

    public function test_byDate_filters(): void
    {
        $this->db->table('product_images')->insert([
            'image_url' => 'img1.jpg',
            'created_at' => '2023-01-01 10:00:00'
        ]);
        $this->db->table('product_images')->insert([
            'image_url' => 'img2.jpg',
            'created_at' => '2023-02-01 10:00:00'
        ]);

        $result = $this->service->byDate(['year' => 2023, 'month' => 1]);
        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('img1.jpg', $result['data'][0]['image_url']);
    }

    public function test_searchSku_short_query_returns_empty(): void
    {
        $result = $this->service->searchSku(['sku' => 'a']);
        $this->assertCount(0, $result['data']);
    }

    public function test_searchSku_finds_match(): void
    {
        $this->db->table('products')->insert(['code' => 'PROD1', 'name' => 'Prod 1', 'id' => 1]);
        $this->db->table('product_images')->insert(['product_id' => 1, 'image_url' => 'found.jpg']);

        $result = $this->service->searchSku(['sku' => 'PROD1']);
        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('found.jpg', $result['data'][0]['image_url']);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }
}
