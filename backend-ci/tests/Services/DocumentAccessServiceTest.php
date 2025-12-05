<?php

namespace Tests\Services;

use App\Services\Permissions\DocumentAccessService;
use App\Services\Audits\AuditService;
use App\Services\Permissions\PermissionService;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @agent-test: DocumentAccessService
 * @agent-pattern: ACL guard + audit
 */
class DocumentAccessServiceTest extends CIUnitTestCase
{
    private SpyPermissionService $permission;
    private SpyAuditService $audit;
    private DocumentAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permission = new SpyPermissionService();
        $this->audit = new SpyAuditService();
        $this->service = new DocumentAccessService($this->permission, $this->audit);
    }

    public function testViewEnforcesReadAccess(): void
    {
        $result = $this->service->view(5, 2, 'order', 9);

        $this->assertTrue($result['success']);
        $this->assertSame('read', $this->permission->lastPermission);
    }

    public function testUpdateRequiresChanges(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->update(5, 2, 'order', 9, []);
    }

    public function testUpdateLogsAuditAndChecksWrite(): void
    {
        $result = $this->service->update(5, 2, 'order', 9, ['status' => 'shipped']);

        $this->assertTrue($result['success']);
        $this->assertSame('write', $this->permission->lastPermission);
        $this->assertSame('update', $this->audit->lastAction);
        $this->assertSame(2, $this->audit->lastCompany);
    }
}

class SpyPermissionService extends PermissionService
{
    public ?string $lastPermission = null;

    public function __construct()
    {
    }

    public function assertDocumentAccess(
        int $userId,
        int $companyId,
        string $entityType,
        int $entityId,
        string $requiredPermission = 'read',
        ?string $role = null
    ): void {
        $this->lastPermission = $requiredPermission;
    }
}

class SpyAuditService extends AuditService
{
    public ?string $lastAction = null;
    public ?int $lastCompany = null;

    public function __construct()
    {
    }

    public function logChange(int $companyId, string $entityType, int $entityId, string $action, array $changes, ?int $actorId = null): array
    {
        $this->lastAction = $action;
        $this->lastCompany = $companyId;
        return ['id' => 1, 'company_id' => $companyId, 'entity_type' => $entityType, 'entity_id' => $entityId, 'action' => $action];
    }
}
