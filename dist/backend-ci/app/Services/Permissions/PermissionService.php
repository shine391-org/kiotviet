<?php

namespace App\Services\Permissions;

use App\Repositories\Companies\CompanyRepository;
use App\Repositories\Permissions\ShareRepository;
use App\Validators\PermissionValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Permission service for company scope and document ACL checks.
 *
 * @agent-service: Permission
 * @agent-pattern: Guard + ACL checks
 * @agent-reusable: HIGH
 */
class PermissionService
{
    protected CompanyRepository $companies;
    protected ShareRepository $shares;
    protected PermissionValidator $validator;

    public function __construct(
        ?CompanyRepository $companies = null,
        ?ShareRepository $shares = null,
        ?PermissionValidator $validator = null
    ) {
        $this->companies = $companies ?? new CompanyRepository();
        $this->shares = $shares ?? new ShareRepository();
        $this->validator = $validator ?? new PermissionValidator();
    }

    /**
     * Ensure a user can access a company with the required permission.
     *
     * @agent-use: Company scoped actions
     * @agent-pattern: Guarded access
     */
    public function assertCompanyAccess(int $userId, int $companyId, ?string $requiredPermission = 'read'): void
    {
        if (! $this->hasCompanyPermission($userId, $companyId, $requiredPermission)) {
            throw new RuntimeException('Access denied for company');
        }
    }

    public function hasCompanyPermission(int $userId, int $companyId, ?string $requiredPermission = 'read'): bool
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('user_id is required');
        }
        $required = $this->validator->validateRequired($requiredPermission ?? 'read') ?? 'read';
        $perm = $this->companies->findPermission($companyId, $userId);
        if (! $perm) {
            return false;
        }
        return $this->permissionSatisfied($required, $perm['permissions']);
    }

    /**
     * Enforce document-level permissions with company scope.
     *
     * @agent-use: Document access guard
     * @agent-pattern: Company + ACL enforcement
     */
    public function assertDocumentAccess(
        int $userId,
        int $companyId,
        string $entityType,
        int $entityId,
        string $requiredPermission = 'read',
        ?string $role = null
    ): void {
        $required = $this->validator->validateRequired($requiredPermission) ?? 'read';
        if ($this->hasCompanyPermission($userId, $companyId, $required)) {
            return;
        }
        $share = $this->shares->findForUser($companyId, $entityType, $entityId, $userId, $role);
        if (! $share || ! $this->permissionSatisfied($required, $share['permissions'])) {
            throw new RuntimeException('Access denied for document');
        }
    }

    /**
     * Apply a company_id filter to a query builder.
     *
     * @agent-use: Repository scope helper
     * @agent-pattern: Company filter hook
     */
    public function applyCompanyScope($builder, int $companyId)
    {
        return $builder->where('company_id', $companyId);
    }

    private function permissionSatisfied(?string $required, array $grants): bool
    {
        if (empty($grants)) {
            return false;
        }
        if (in_array('admin', $grants, true)) {
            return true;
        }
        if ($required === null) {
            return true;
        }
        return in_array($required, $grants, true);
    }
}
