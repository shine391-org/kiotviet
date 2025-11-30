<?php

namespace Tests\Services;

use App\Services\Assets\AssetService;
use App\Repositories\Assets\AssetRepository;
use App\Validators\AssetValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: AssetService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class AssetServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private AssetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $repo = new AssetRepository(null, $this->db);
        $this->service = new AssetService($repo, new AssetValidator());
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_and_capitalizes_asset()
    {
        $asset = $this->service->create(['asset_name' => 'Laptop', 'cost' => 1000])['data'];
        $this->assertEquals('draft', $asset['status']);
        $this->assertStringStartsWith('AST-', $asset['asset_number']);

        $active = $this->service->capitalize($asset['id'])['data'];
        $this->assertEquals('active', $active['status']);
    }

    /** @test */
    public function it_disposes_asset()
    {
        $asset = $this->service->create(['asset_name' => 'Printer'])['data'];
        $disposed = $this->service->dispose($asset['id'])['data'];
        $this->assertEquals('disposed', $disposed['status']);
    }
}
