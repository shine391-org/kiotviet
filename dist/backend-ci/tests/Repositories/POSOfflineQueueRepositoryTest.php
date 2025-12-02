<?php

namespace Tests\Repositories;

use App\Repositories\POS\POSOfflineQueueRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\POSSchemaTrait;

/**
 * @agent-test: POSOfflineQueueRepository MySQL testing
 * @agent-pattern: Repository test with DevDatabaseTrait
 */
class POSOfflineQueueRepositoryTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use POSSchemaTrait;

    private POSOfflineQueueRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetPOSSchema();
        $this->repo = new POSOfflineQueueRepository();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_upserts_and_marks_synced()
    {
        $row = $this->repo->upsert([
            'temp_id' => 'tmp-1',
            'device_id' => 'dev-1',
            'payload' => ['foo' => 'bar'],
        ]);
        $this->assertEquals('tmp-1', $row['temp_id']);

        $this->repo->markSynced($row['id'], 123);
        $after = $this->repo->findByKey('tmp-1', 'dev-1');
        $this->assertEquals('synced', $after['status']);
        $this->assertEquals(123, $after['order_id']);
    }

    /** @test */
    public function it_is_idempotent_by_device_and_temp()
    {
        $first = $this->repo->upsert([
            'temp_id' => 'tmp-2',
            'device_id' => 'dev-1',
            'payload' => ['a' => 1],
        ]);
        $second = $this->repo->upsert([
            'temp_id' => 'tmp-2',
            'device_id' => 'dev-1',
            'payload' => ['a' => 2],
        ]);

        $this->assertEquals($first['id'], $second['id']);
        $this->assertEquals(['a' => 2], $second['payload']);
    }
}
