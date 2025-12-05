<?php

namespace App\Services\Audits;

use App\Repositories\Audits\AuditLogRepository;
use App\Services\Permissions\PermissionService;
use InvalidArgumentException;
use RuntimeException;

/**
 * Audit service to log and query document history.
 *
 * @agent-service: Audit
 * @agent-pattern: Log + list
 * @agent-reusable: MEDIUM
 */
class AuditService
{
    protected AuditLogRepository $logs;
    protected PermissionService $permission;

    public function __construct(?AuditLogRepository $logs = null, ?PermissionService $permission = null)
    {
        $this->logs = $logs ?? new AuditLogRepository();
        $this->permission = $permission ?? new PermissionService();
    }

    /**
     * Log a change/event.
     *
     * @agent-use: Internal audit
     * @agent-pattern: Append-only log
     */
    public function logChange(int $companyId, string $entityType, int $entityId, string $action, array $changes, ?int $actorId = null): array
    {
        if ($companyId <= 0) {
            throw new InvalidArgumentException('company_id is required for audit log');
        }
        return $this->logs->log([
            'company_id' => $companyId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'changes' => $changes,
            'actor_id' => $actorId,
        ]);
    }

    /**
     * List audit logs for a company and optional entity.
     *
     * @agent-use: GET /api/audit-logs
     * @agent-pattern: Guarded list
     */
    public function list(array $filters, ?int $userId = null): array
    {
        if (empty($filters['company_id'])) {
            throw new InvalidArgumentException('company_id is required');
        }
        if ($userId !== null) {
            try {
                $this->permission->assertCompanyAccess($userId, (int) $filters['company_id'], 'read');
            } catch (RuntimeException $e) {
                if (! empty($filters['entity_type']) && ! empty($filters['entity_id'])) {
                    $this->permission->assertDocumentAccess(
                        $userId,
                        (int) $filters['company_id'],
                        (string) $filters['entity_type'],
                        (int) $filters['entity_id'],
                        'read'
                    );
                } else {
                    throw $e;
                }
            }
        }
        return ['success' => true, 'data' => $this->logs->list($filters)];
    }
}
