<?php

namespace Tests\Repositories;

use App\Repositories\PriceLists\PriceListRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\PriceListSchemaTrait;

/**
 * @agent-test: PriceListRepository
 * @agent-pattern: Repository test with DevDatabaseTrait + PriceListSchemaTrait
 */
class PriceListRepositoryTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use PriceListSchemaTrait;

    private PriceListRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetPriceListSchema();
        $this->repo = new PriceListRepository(null, $this->db);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function testFindAllFiltersByStatusAndGroup(): void
    {
        $today = date('Y-m-d');
        $global = $this->createPriceList(['name' => 'Global', 'apply_to_groups' => null, 'is_active' => 1, 'start_date' => $today]);
        $grouped = $this->createPriceList(['name' => 'Group 5', 'apply_to_groups' => [5], 'is_active' => 1, 'start_date' => $today]);
        $this->createPriceList(['name' => 'Upcoming', 'apply_to_groups' => [5], 'is_active' => 1, 'start_date' => date('Y-m-d', strtotime('+5 days'))]);
        $this->createPriceList(['name' => 'Inactive', 'apply_to_groups' => [5], 'is_active' => 0]);

        $result = $this->repo->findAll([
            'status' => 'active',
            'apply_to_group_id' => 5,
            'page' => 1,
            'limit' => 10,
        ]);

        $this->assertCount(2, $result);
        $this->assertSame([5], $result[0]['apply_to_groups']);
        $this->assertSame($global['name'], $result[1]['name']);
        $this->assertSame(2, $this->repo->count(['status' => 'active', 'apply_to_group_id' => 5]));
    }

    public function testNameExistsRespectsExcludeId(): void
    {
        $row = $this->createPriceList(['name' => 'Wholesale']);

        $this->assertTrue($this->repo->nameExists('Wholesale'));
        $this->assertFalse($this->repo->nameExists('Wholesale', $row['id']));
    }

    public function testApplicablePriceListsFiltersByDateAndGroup(): void
    {
        $today = date('Y-m-d');
        $this->createPriceList(['name' => 'Active', 'start_date' => $today, 'end_date' => $today, 'apply_to_groups' => [3]]);
        $this->createPriceList(['name' => 'No group', 'apply_to_groups' => null]);
        $this->createPriceList(['name' => 'Expired', 'end_date' => date('Y-m-d', strtotime('-1 day'))]);
        $this->createPriceList(['name' => 'Inactive', 'is_active' => 0]);

        $applicable = $this->repo->applicablePriceLists(3, $today);

        $this->assertCount(2, $applicable);
        $names = array_column($applicable, 'name');
        $this->assertEqualsCanonicalizing(['Active', 'No group'], $names);
    }

    private function createPriceList(array $overrides = []): array
    {
        $now = date('Y-m-d H:i:s');
        $data = array_merge([
            'name' => 'PL-' . uniqid(),
            'type' => 'custom',
            'description' => null,
            'apply_to_groups' => null,
            'start_date' => null,
            'end_date' => null,
            'priority' => 0,
            'is_active' => 1,
            'formula' => null,
            'base_price_list_id' => null,
            'auto_update' => 0,
            'rounding_rule' => 'none',
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ], $overrides);

        if (isset($data['apply_to_groups']) && is_array($data['apply_to_groups'])) {
            $data['apply_to_groups'] = json_encode($data['apply_to_groups']);
        }

        $this->db->table('price_lists')->insert($data);
        $data['id'] = (int) $this->db->insertID();
        return $data;
    }
}
