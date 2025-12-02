<?php

namespace Tests\Services;

use App\Repositories\Companies\CompanyRepository;
use App\Services\Audits\AuditService;
use App\Services\Permissions\PermissionService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\CompleteSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: AuditService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class AuditServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private AuditService $service;
    private CompanyRepository $companies;
    private int $companyId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->companies = new CompanyRepository(null, null, $this->db);
        $permissionService = new PermissionService($this->companies);
        $this->service = new AuditService(null, $permissionService);

        $this->companyId = $this->companies->create(['name' => 'Delta', 'code' => 'DELTA'])['id'];
        $this->companies->assignPermission([
            'company_id' => $this->companyId,
            'user_id' => 1,
            'permissions' => ['read'],
        ]);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_logs_and_lists_audit_records()
    {
        $this->service->logChange($this->companyId, 'order', 10, 'update', ['status' => 'approved'], 1);
        $res = $this->service->list([
            'company_id' => $this->companyId,
            'entity_type' => 'order',
            'entity_id' => 10,
        ], 1);

        $this->assertTrue($res['success']);
        $this->assertCount(1, $res['data']);
        $this->assertEquals('approved', $res['data'][0]['changes']['status']);
    }

    /** @test */
    public function it_blocks_list_without_permission()
    {
        $this->service->logChange($this->companyId, 'order', 11, 'update', ['status' => 'draft'], 1);
        $this->expectException(\RuntimeException::class);
        $this->service->list([
            'company_id' => $this->companyId,
            'entity_type' => 'order',
        ], 99);
    }
}
