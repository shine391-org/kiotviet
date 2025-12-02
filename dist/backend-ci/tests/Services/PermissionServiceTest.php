<?php

namespace Tests\Services;

use App\Repositories\Companies\CompanyRepository;
use App\Repositories\Permissions\ShareRepository;
use App\Services\Permissions\PermissionService;
use App\Validators\PermissionValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\CompleteSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: PermissionService
 * @agent-pattern: DevDatabaseTrait + CompleteSchemaTrait
 */
class PermissionServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private PermissionService $service;
    private CompanyRepository $companies;
    private ShareRepository $shares;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->companies = new CompanyRepository(null, null, $this->db);
        $this->shares = new ShareRepository(null, $this->db);
        $this->service = new PermissionService($this->companies, $this->shares, new PermissionValidator());
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_allows_user_with_company_permission()
    {
        $company = $this->companies->create(['name' => 'ACME', 'code' => 'ACME']);
        $this->companies->assignPermission([
            'company_id' => $company['id'],
            'user_id' => 1,
            'permissions' => ['read', 'write'],
        ]);

        $this->service->assertCompanyAccess(1, $company['id'], 'write');
        $this->assertTrue(true);
    }

    /** @test */
    public function it_denies_user_without_company_permission()
    {
        $company = $this->companies->create(['name' => 'ACME', 'code' => 'ACME-2']);
        $this->expectException(\RuntimeException::class);
        $this->service->assertCompanyAccess(2, $company['id'], 'read');
    }

    /** @test */
    public function it_allows_document_access_via_share()
    {
        $company = $this->companies->create(['name' => 'Beta', 'code' => 'BETA']);
        $this->shares->create([
            'company_id' => $company['id'],
            'entity_type' => 'order',
            'entity_id' => 77,
            'shared_with_user_id' => 3,
            'permissions' => ['read'],
        ]);

        $this->service->assertDocumentAccess(3, $company['id'], 'order', 77, 'read');

        $this->expectException(\RuntimeException::class);
        $this->service->assertDocumentAccess(3, $company['id'], 'order', 77, 'write');
    }
}
