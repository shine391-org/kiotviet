<?php

namespace Tests\Repositories;

use App\Repositories\Companies\CompanyRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\RbacSchemaTrait;

/**
 * @agent-test: CompanyRepository
 * @agent-pattern: RBAC repository coverage with DevDatabaseTrait
 */
class CompanyRepositoryTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use RbacSchemaTrait;

    private CompanyRepository $repo;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetRbacSchema();
        $this->userId = $this->createUser();
        $this->repo = new CompanyRepository(null, null, $this->db);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function testCreateSetsDefaultAndClearsPrevious(): void
    {
        $first = $this->repo->create(['code' => 'C1', 'name' => 'Company 1', 'is_default' => false]);
        $second = $this->repo->create(['code' => 'C2', 'name' => 'Company 2', 'is_default' => true]);
        $third = $this->repo->create(['code' => 'C3', 'name' => 'Company 3', 'is_default' => true]);

        $firstRow = $this->db->table('companies')->where('id', $first['id'])->get()->getRowArray();
        $secondRow = $this->db->table('companies')->where('id', $second['id'])->get()->getRowArray();
        $thirdRow = $this->db->table('companies')->where('id', $third['id'])->get()->getRowArray();

        $this->assertSame('0', (string) $firstRow['is_default']);
        $this->assertSame('0', (string) $secondRow['is_default']);
        $this->assertSame('1', (string) $thirdRow['is_default']);
    }

    public function testAssignPermissionUpsertsByUser(): void
    {
        $company = $this->repo->create(['code' => 'RBAC', 'name' => 'RBAC Co']);

        $first = $this->repo->assignPermission([
            'company_id' => $company['id'],
            'user_id' => $this->userId,
            'permissions' => ['read'],
        ]);
        $second = $this->repo->assignPermission([
            'company_id' => $company['id'],
            'user_id' => $this->userId,
            'permissions' => ['write'],
        ]);

        $this->assertSame($first['id'], $second['id']);
        $this->assertSame(['write'], $second['permissions']);

        $listed = $this->repo->listPermissions($company['id']);
        $this->assertCount(1, $listed);
        $this->assertSame(['write'], $listed[0]['permissions']);
    }

    private function createUser(): int
    {
        $now = date('Y-m-d H:i:s');
        $data = [
            'username' => 'user_' . uniqid(),
            'email' => uniqid() . '@example.com',
            'password' => 'hash',
            'full_name' => 'Test User',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $this->db->table('users')->insert($data);
        return (int) $this->db->insertID();
    }
}
