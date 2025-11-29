<?php

namespace Tests\Repositories;

use App\Repositories\Quality\QualityParameterRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\QualitySchemaTrait;

/**
 * @agent-test: QualityParameterRepository
 * @agent-pattern: Repository test with DevDatabaseTrait
 */
class QualityParameterRepositoryTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use QualitySchemaTrait;

    private QualityParameterRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetQualitySchema();
        $this->repo = new QualityParameterRepository(null, $this->db);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function test_create_and_find_parameter(): void
    {
        $created = $this->repo->create([
            'name' => 'Temperature',
            'uom' => 'C',
            'min_value' => 2.5,
            'max_value' => 8.0,
        ]);

        $found = $this->repo->find($created['id']);

        $this->assertNotNull($found);
        $this->assertSame('Temperature', $found['name']);
        $this->assertEquals(2.5, (float) $found['min_value']);
        $this->assertEquals(8.0, (float) $found['max_value']);
    }

    public function test_list_filters_by_active_flag(): void
    {
        $this->repo->create(['name' => 'Active Param', 'is_active' => true]);
        $this->repo->create(['name' => 'Inactive Param', 'is_active' => false]);

        $active = $this->repo->list(['is_active' => true]);
        $inactive = $this->repo->list(['is_active' => false]);

        $this->assertCount(1, $active);
        $this->assertSame('Active Param', $active[0]['name']);
        $this->assertCount(1, $inactive);
        $this->assertSame('Inactive Param', $inactive[0]['name']);
    }
}
