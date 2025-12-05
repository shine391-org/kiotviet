<?php

namespace App\Services\Permissions;

use App\Repositories\Permissions\ShareRepository;
use App\Services\Audits\AuditService;
use App\Validators\ShareValidator;
use InvalidArgumentException;

/**
 * Sharing service to share/unshare documents.
 *
 * @agent-service: Sharing
 * @agent-pattern: ACL share management
 * @agent-reusable: MEDIUM
 */
class SharingService
{
    protected ShareRepository $shares;
    protected ShareValidator $validator;
    protected PermissionService $permission;
    protected AuditService $audit;

    public function __construct(
        ?ShareRepository $shares = null,
        ?ShareValidator $validator = null,
        ?PermissionService $permission = null,
        ?AuditService $audit = null
    ) {
        $this->shares = $shares ?? new ShareRepository();
        $this->validator = $validator ?? new ShareValidator();
        $this->permission = $permission ?? new PermissionService();
        $this->audit = $audit ?? new AuditService();
    }

    /**
     * Share a document to a user/role.
     *
     * @agent-use: POST /api/shares
     * @agent-pattern: Guarded share
     */
    public function share(int $actorId, array $input): array
    {
        $payload = $this->validator->validateShare($input);
        $this->permission->assertCompanyAccess($actorId, $payload['company_id'], 'share');
        $share = $this->shares->create($payload);
        $this->audit->logChange($payload['company_id'], $payload['entity_type'], $payload['entity_id'], 'share', [
            'shared_with_user_id' => $payload['shared_with_user_id'],
            'shared_with_role' => $payload['shared_with_role'],
            'permissions' => $payload['permissions'],
        ], $actorId);
        return ['success' => true, 'data' => $share];
    }

    /**
     * List shares for a company/doc (actor checked).
     *
     * @agent-use: GET /api/shares
     * @agent-pattern: Guarded list
     */
    public function list(int $actorId, array $filters = []): array
    {
        $validated = $this->validator->validateListFilters($filters);
        if (empty($validated['company_id'])) {
            throw new InvalidArgumentException('company_id is required to list shares');
        }
        $this->permission->assertCompanyAccess($actorId, $validated['company_id'], 'read');
        return ['success' => true, 'data' => $this->shares->list($validated)];
    }

    /**
     * Remove a share.
     *
     * @agent-use: DELETE /api/shares/{id}
     * @agent-pattern: Guarded delete
     */
    public function unshare(int $actorId, int $shareId): array
    {
        $id = $this->validator->validateUnshare($shareId);
        $share = $this->shares->find($id);
        if (! $share) {
            throw new InvalidArgumentException('Share not found');
        }
        $this->permission->assertCompanyAccess($actorId, (int) $share['company_id'], 'share');
        $this->shares->delete($id);
        $this->audit->logChange((int) $share['company_id'], $share['entity_type'], (int) $share['entity_id'], 'unshare', [
            'share_id' => $id,
        ], $actorId);
        return ['success' => true];
    }
}
