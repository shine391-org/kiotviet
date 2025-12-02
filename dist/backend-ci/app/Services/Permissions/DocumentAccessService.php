<?php

namespace App\Services\Permissions;

use App\Services\Audits\AuditService;
use InvalidArgumentException;

/**
 * Lightweight document access facade using company + share ACL.
 *
 * @agent-service: Document access
 * @agent-pattern: Guard + audit
 * @agent-reusable: MEDIUM
 */
class DocumentAccessService
{
    protected PermissionService $permission;
    protected AuditService $audit;

    public function __construct(?PermissionService $permission = null, ?AuditService $audit = null)
    {
        $this->permission = $permission ?? new PermissionService();
        $this->audit = $audit ?? new AuditService();
    }

    /**
     * View a document (ACL enforced).
     *
     * @agent-use: GET /api/documents/{company}/{type}/{id}
     * @agent-pattern: ACL check then return stub
     */
    public function view(int $userId, int $companyId, string $entityType, int $entityId): array
    {
        $this->permission->assertDocumentAccess($userId, $companyId, $entityType, $entityId, 'read');
        return [
            'success' => true,
            'data' => [
                'company_id' => $companyId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ],
        ];
    }

    /**
     * Update a document (ACL enforced) and log audit trail.
     *
     * @agent-use: PUT /api/documents/{company}/{type}/{id}
     * @agent-pattern: ACL + audit
     */
    public function update(int $userId, int $companyId, string $entityType, int $entityId, array $changes): array
    {
        if (empty($changes)) {
            throw new InvalidArgumentException('changes are required for update');
        }
        $this->permission->assertDocumentAccess($userId, $companyId, $entityType, $entityId, 'write');
        $this->audit->logChange($companyId, $entityType, $entityId, 'update', $changes, $userId);
        return [
            'success' => true,
            'data' => [
                'company_id' => $companyId,
                'entity_id' => $entityId,
            ],
        ];
    }
}
