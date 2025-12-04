<?php

namespace Tests\Repositories;

use App\Models\DocumentShareModel;
use App\Repositories\Permissions\ShareRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\RbacSchemaTrait;

/**
 * @agent-test: ShareRepository
 * @agent-pattern: Repository test with DevDatabaseTrait + RbacSchemaTrait
 */
class ShareRepositoryTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use RbacSchemaTrait;

    private ShareRepository $repo;
    private int $companyId;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetRbacSchema();

        $this->companyId = $this->createCompany();
        $this->userId = $this->createUser();
        $this->repo = new ShareRepository(new DocumentShareModel($this->db), $this->db);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function testCreateInsertsNewShare(): void
    {
        $share = $this->repo->create([
            'company_id' => $this->companyId,
            'entity_type' => 'order',
            'entity_id' => 9,
            'shared_with_user_id' => $this->userId,
            'permissions' => ['read', 'write'],
            'created_by' => $this->userId,
        ]);

        $this->assertNotNull($share['id']);
        $this->assertSame(['read', 'write'], $share['permissions']);

        $row = $this->db->table('document_shares')->where('id', $share['id'])->get()->getRowArray();
        $this->assertSame(['read', 'write'], json_decode((string) $row['permissions'], true));
    }

    public function testCreateUpdatesExistingShare(): void
    {
        $first = $this->repo->create([
            'company_id' => $this->companyId,
            'entity_type' => 'order',
            'entity_id' => 9,
            'shared_with_user_id' => $this->userId,
            'permissions' => ['read'],
        ]);

        $updated = $this->repo->create([
            'company_id' => $this->companyId,
            'entity_type' => 'order',
            'entity_id' => 9,
            'shared_with_user_id' => $this->userId,
            'permissions' => ['write'],
        ]);

        $this->assertSame($first['id'], $updated['id']);
        $this->assertSame(['write'], $updated['permissions']);
    }

    public function testFindForUserMatchesRoleOrUser(): void
    {
        $this->repo->create([
            'company_id' => $this->companyId,
            'entity_type' => 'doc',
            'entity_id' => 5,
            'shared_with_role' => 'manager',
            'permissions' => ['read'],
        ]);

        $this->repo->create([
            'company_id' => $this->companyId,
            'entity_type' => 'doc',
            'entity_id' => 5,
            'shared_with_user_id' => $this->userId,
            'permissions' => ['write'],
        ]);

        $byUser = $this->repo->findForUser($this->companyId, 'doc', 5, $this->userId, null);
        $this->assertSame(['write'], $byUser['permissions']);

        $byRole = $this->repo->findForUser($this->companyId, 'doc', 5, 999, 'manager');
        $this->assertSame(['read'], $byRole['permissions']);
    }

    public function testListFiltersAndDelete(): void
    {
        $s1 = $this->repo->create([
            'company_id' => $this->companyId,
            'entity_type' => 'invoice',
            'entity_id' => 1,
            'shared_with_user_id' => $this->userId,
            'permissions' => ['read'],
        ]);
        $this->repo->create([
            'company_id' => $this->companyId,
            'entity_type' => 'order',
            'entity_id' => 2,
            'shared_with_user_id' => $this->userId,
            'permissions' => ['read'],
        ]);

        $list = $this->repo->list(['company_id' => $this->companyId, 'entity_type' => 'invoice']);
        $this->assertCount(1, $list);
        $this->assertSame($s1['id'], $list[0]['id']);

        $this->repo->delete($s1['id']);
        $this->assertNull($this->repo->find($s1['id']));
    }

    private function createCompany(): int
    {
        $now = date('Y-m-d H:i:s');
        $data = [
            'code' => 'C-' . uniqid(),
            'name' => 'Company ' . uniqid(),
            'is_default' => 0,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $this->db->table('companies')->insert($data);
        return (int) $this->db->insertID();
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
