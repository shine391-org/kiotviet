<?php

namespace Tests\Services;

use App\Repositories\PaymentMethods\PaymentMethodRepository;
use App\Services\PaymentMethods\PaymentMethodService;
use App\Transformers\PaymentMethodTransformer;
use App\Validators\PaymentMethodValidator;
use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use Tests\Support\Database\CompleteSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: PaymentMethodService
 * @agent-pattern: MySQL-only test with DevDatabaseTrait
 */
class PaymentMethodServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private PaymentMethodService $service;
    private PaymentMethodRepository $repo;
    private CacheInterface $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();

        $this->repo = new PaymentMethodRepository(null, $this->db);
        $validator = new PaymentMethodValidator();
        $transformer = new PaymentMethodTransformer();
        $this->cache = \Config\Services::cache(null, false);
        $this->cache->clean();

        $this->service = new PaymentMethodService($this->repo, $validator, $transformer, $this->cache);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_lists_only_active_methods_by_default_sorted()
    {
        $idA = $this->repo->create([
            'code' => 'CASH',
            'name' => 'Cash',
            'display_order' => 2,
            'is_active' => true,
        ])['id'];
        $idB = $this->repo->create([
            'code' => 'CARD',
            'name' => 'Card',
            'display_order' => 1,
            'is_active' => true,
        ])['id'];
        $this->repo->create([
            'code' => 'HIDDEN',
            'name' => 'Hidden',
            'is_active' => false,
            'display_order' => 0,
        ]);

        $result = $this->service->list([]);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']);
        $this->assertEquals('CARD', $result['data'][0]['code']);
        $this->assertEquals('CASH', $result['data'][1]['code']);
        $this->assertEquals(2, $result['pagination']['total']);

        // second call uses cache path
        $again = $this->service->list([]);
        $this->assertCount(2, $again['data']);
        $this->assertEquals($result['data'][0]['code'], $again['data'][0]['code']);
    }

    /** @test */
    public function it_creates_method_and_blocks_duplicate_code()
    {
        $created = $this->service->create([
            'code' => 'BANK_TRANSFER',
            'name' => 'Bank transfer',
            'description' => 'via bank',
            'display_order' => 5,
        ]);

        $this->assertTrue($created['success']);
        $this->assertNotEmpty($created['data']['id']);
        $this->assertEquals('BANK_TRANSFER', $created['data']['code']);

        $this->expectException(InvalidArgumentException::class);
        $this->service->create([
            'code' => 'BANK_TRANSFER',
            'name' => 'Dup',
        ]);
    }

    /** @test */
    public function it_blocks_delete_when_used_in_orders()
    {
        $method = $this->repo->create([
            'code' => 'COD',
            'name' => 'Cash on delivery',
            'is_active' => true,
        ]);

        $this->db->table('orders')->insert([
            'payment_method' => 'COD',
            'total' => 100000,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->delete($method['id']);
    }

    /** @test */
    public function it_updates_and_toggles_active_state()
    {
        $method = $this->repo->create([
            'code' => 'EWALLET',
            'name' => 'E Wallet',
            'is_active' => true,
        ]);

        $updated = $this->service->update($method['id'], [
            'name' => 'Ví điện tử',
            'display_order' => 10,
            'name_translations' => ['vi' => 'Ví điện tử', 'en' => 'E-Wallet'],
        ]);

        $this->assertEquals('Ví điện tử', $updated['data']['name']);
        $this->assertEquals(10, $updated['data']['display_order']);
        $this->assertEquals('E-Wallet', $updated['data']['name_translations']['en']);

        $deactivated = $this->service->deactivate($method['id']);
        $this->assertFalse($deactivated['data']['is_active']);

        $activated = $this->service->activate($method['id']);
        $this->assertTrue($activated['data']['is_active']);
    }
}
