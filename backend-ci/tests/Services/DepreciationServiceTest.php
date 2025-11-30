<?php

namespace Tests\Services;

use App\Services\Assets\AssetService;
use App\Services\Assets\DepreciationService;
use App\Repositories\Assets\AssetRepository;
use App\Repositories\Assets\DepreciationScheduleRepository;
use App\Repositories\Accounting\GLEntryRepository;
use App\Validators\AssetValidator;
use App\Validators\DepreciationValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: DepreciationService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class DepreciationServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private DepreciationService $service;
    private AssetService $assets;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $assetRepo = new AssetRepository(null, $this->db);
        $depRepo = new DepreciationScheduleRepository(null, null, $this->db);
        $glRepo = new GLEntryRepository(null, $this->db);
        $this->assets = new AssetService($assetRepo, new AssetValidator());
        $this->service = new DepreciationService($depRepo, $assetRepo, $glRepo, new DepreciationValidator());
        $this->seedAccounts();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_generates_schedule_and_posts_gl()
    {
        $asset = $this->assets->create(['asset_name' => 'Server', 'cost' => 1200, 'salvage_value' => 0])['data'];
        $schedule = $this->service->createSchedule([
            'asset_id' => $asset['id'],
            'rate' => 12,
            'total_periods' => 12,
            'start_date' => '2025-01-01',
        ])['data'];

        $this->assertCount(12, $schedule['lines']);
        $this->assertEquals(12, $schedule['total_periods']);
        $line = $schedule['lines'][0];

        $posted = $this->service->postLine([
            'schedule_id' => $schedule['id'],
            'period_no' => 1,
            'account_id' => 1,
            'expense_account_id' => 2,
        ])['data'];

        $this->assertNotEmpty($posted['posted_gl_entry_id']);
        $gl = $this->db->table('gl_entries')->get()->getResultArray();
        $this->assertCount(2, $gl);
    }

    private function seedAccounts(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('chart_of_accounts')->insert(['id' => 1, 'name' => 'Asset Acc', 'account_type' => 'asset', 'created_at' => $now]);
        $this->db->table('chart_of_accounts')->insert(['id' => 2, 'name' => 'Expense Acc', 'account_type' => 'expense', 'created_at' => $now]);
    }
}
