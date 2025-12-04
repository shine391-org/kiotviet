<?php

namespace Tests\Services;

use App\Repositories\Permissions\ShareRepository;
use App\Services\Audits\AuditService;
use App\Services\Permissions\PermissionService;
use App\Services\Permissions\SharingService;
use App\Validators\ShareValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @agent-test: SharingService
 * @agent-pattern: Guarded share workflows
 */
class SharingServiceTest extends CIUnitTestCase
{
    private InMemoryShareRepository $repo;
    private SharingPermissionStub $permission;
    private AuditShareSpy $audit;
    private SharingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new InMemoryShareRepository();
        $this->permission = new SharingPermissionStub();
        $this->audit = new AuditShareSpy();
        $this->service = new SharingService($this->repo, new ShareValidator(), $this->permission, $this->audit);
    }

    public function testShareCreatesRecordAndAudits(): void
    {
        $result = $this->service->share(9, [
            'company_id' => 1,
            'entity_type' => 'invoice',
            'entity_id' => 3,
            'shared_with_user_id' => 4,
            'permissions' => ['read', 'write'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $this->repo->storage);
        $this->assertSame('share', $this->permission->lastPermission);
        $this->assertSame('share', $this->audit->lastAction);
    }

    public function testListRequiresCompanyId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->list(1, []);
    }

    public function testUnshareRemovesShareAndAudits(): void
    {
        $shared = $this->service->share(9, [
            'company_id' => 2,
            'entity_type' => 'order',
            'entity_id' => 8,
            'shared_with_user_id' => 5,
            'permissions' => ['read'],
        ]);
        $id = $shared['data']['id'];

        $result = $this->service->unshare(9, $id);

        $this->assertTrue($result['success']);
        $this->assertNull($this->repo->find($id));
        $this->assertSame('share', $this->permission->lastPermission);
        $this->assertSame('unshare', $this->audit->lastAction);
    }
}

class InMemoryShareRepository extends ShareRepository
{
    public array $storage = [];
    private int $lastId = 0;

    public function __construct()
    {
    }

    public function create(array $data): array
    {
        $id = ++$this->lastId;
        $share = $data;
        $share['id'] = $id;
        $share['permissions'] = $data['permissions'] ?? [];
        $this->storage[$id] = $share;
        return $share;
    }

    public function find(int $id): ?array
    {
        return $this->storage[$id] ?? null;
    }

    public function list(array $filters = []): array
    {
        return array_values(array_filter($this->storage, static function ($row) use ($filters) {
            foreach (['company_id', 'entity_id', 'shared_with_user_id'] as $field) {
                if (isset($filters[$field]) && ($row[$field] ?? null) !== $filters[$field]) {
                    return false;
                }
            }
            if (! empty($filters['entity_type']) && ($row['entity_type'] ?? null) !== $filters['entity_type']) {
                return false;
            }
            return true;
        }));
    }

    public function delete(int $id): void
    {
        unset($this->storage[$id]);
    }
}

class SharingPermissionStub extends PermissionService
{
    public ?string $lastPermission = null;
    public ?int $lastCompany = null;

    public function __construct()
    {
    }

    public function assertCompanyAccess(int $userId, int $companyId, ?string $requiredPermission = 'read'): void
    {
        $this->lastPermission = $requiredPermission ?? null;
        $this->lastCompany = $companyId;
    }
}

class AuditShareSpy extends AuditService
{
    public ?string $lastAction = null;

    public function __construct()
    {
    }

    public function logChange(int $companyId, string $entityType, int $entityId, string $action, array $changes, ?int $actorId = null): array
    {
        $this->lastAction = $action;
        return ['success' => true, 'action' => $action, 'company_id' => $companyId, 'entity_type' => $entityType];
    }
}
