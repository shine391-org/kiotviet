<?php

namespace Tests\Services;

use App\Repositories\Companies\CompanyRepository;
use App\Repositories\Permissions\ShareRepository;
use App\Services\Permissions\PermissionService;
use App\Validators\PermissionValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use RuntimeException;

/**
 * @agent-test: PermissionService
 * @agent-pattern: RBAC guard coverage
 */
class PermissionServiceTest extends CIUnitTestCase
{
    private FakeCompanyRepository $companies;
    private FakeShareRepository $shares;
    private PermissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->companies = new FakeCompanyRepository();
        $this->shares = new FakeShareRepository();
        $this->service = new PermissionService($this->companies, $this->shares, new PermissionValidator());
    }

    public function testHasCompanyPermissionRequiresValidUser(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->hasCompanyPermission(0, 1, 'read');
    }

    public function testHasCompanyPermissionAcceptsAdminGrant(): void
    {
        $this->companies->permissionMap[1][5] = ['permissions' => ['admin']];

        $this->assertTrue($this->service->hasCompanyPermission(5, 1, 'write'));
    }

    public function testAssertDocumentAccessUsesShareFallback(): void
    {
        $this->shares->share = [
            'company_id' => 1,
            'entity_type' => 'order',
            'entity_id' => 9,
            'permissions' => ['write'],
        ];

        $this->service->assertDocumentAccess(7, 1, 'order', 9, 'write');

        $this->assertSame('order', $this->shares->lookups[0]['entity_type']);
    }

    public function testAssertDocumentAccessThrowsWhenDenied(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service->assertDocumentAccess(7, 1, 'order', 9, 'write');
    }
}

class FakeCompanyRepository extends CompanyRepository
{
    public array $permissionMap = [];

    public function __construct()
    {
        $this->permissions = new \App\Models\CompanyPermissionModel();
    }

    public function findPermission(int $companyId, int $userId): ?array
    {
        return $this->permissionMap[$companyId][$userId] ?? null;
    }
}

class FakeShareRepository extends ShareRepository
{
    public ?array $share = null;
    public array $lookups = [];

    public function __construct()
    {
    }

    public function findForUser(int $companyId, string $entityType, int $entityId, int $userId, ?string $role = null): ?array
    {
        $this->lookups[] = [
            'company_id' => $companyId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $userId,
            'role' => $role,
        ];
        return $this->share;
    }
}
