<?php

namespace Tests\Services;

use App\Repositories\Companies\CompanyRepository;
use App\Repositories\Permissions\ShareRepository;
use App\Services\Permissions\SharingService;
use App\Validators\PermissionValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\CompleteSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: SharingService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class SharingServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private SharingService $service;
    private CompanyRepository $companies;
    private ShareRepository $shares;
    private int $companyId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->companies = new CompanyRepository(null, null, $this->db);
        $this->shares = new ShareRepository(null, $this->db);
        $this->service = new SharingService(
            $this->shares,
            null,
            null,
            null
        );
        $this->companyId = $this->companies->create(['name' => 'Gamma', 'code' => 'GAMMA'])['id'];
        $this->companies->assignPermission([
            'company_id' => $this->companyId,
            'user_id' => 1,
            'permissions' => (new PermissionValidator())->normalizePermissions(['share']),
        ]);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_share_with_permissions()
    {
        $res = $this->service->share(1, [
            'company_id' => $this->companyId,
            'entity_type' => 'invoice',
            'entity_id' => 15,
            'shared_with_user_id' => 2,
            'permissions' => ['read', 'write'],
        ]);

        $this->assertTrue($res['success']);
        $this->assertNotEmpty($res['data']['id']);
        $this->assertContains('read', $res['data']['permissions']);
        $this->assertContains('write', $res['data']['permissions']);
    }

    /** @test */
    public function it_unshares_document()
    {
        $share = $this->service->share(1, [
            'company_id' => $this->companyId,
            'entity_type' => 'invoice',
            'entity_id' => 20,
            'shared_with_user_id' => 5,
            'permissions' => ['read'],
        ])['data'];

        $result = $this->service->unshare(1, $share['id']);
        $this->assertTrue($result['success']);
        $this->assertNull($this->shares->find($share['id']));
    }
}
