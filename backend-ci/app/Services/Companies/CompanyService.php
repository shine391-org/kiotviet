<?php

namespace App\Services\Companies;

use App\Repositories\Companies\CompanyRepository;
use App\Repositories\Permissions\ShareRepository;
use App\Services\Permissions\PermissionService;
use App\Validators\CompanyValidator;
use App\Validators\PermissionValidator;

/**
 * Company service for CRUD and permission assignments.
 *
 * @agent-service: Company
 * @agent-pattern: Service delegating to repository
 * @agent-reusable: MEDIUM
 */
class CompanyService
{
    protected CompanyRepository $companies;
    protected CompanyValidator $validator;
    protected PermissionValidator $permissionValidator;
    protected PermissionService $permissionService;

    public function __construct(
        ?CompanyRepository $companies = null,
        ?CompanyValidator $validator = null,
        ?PermissionValidator $permissionValidator = null,
        ?PermissionService $permissionService = null
    ) {
        $this->validator = $validator ?? new CompanyValidator();
        $this->permissionValidator = $permissionValidator ?? new PermissionValidator();
        $this->companies = $companies ?? new CompanyRepository();
        $this->permissionService = $permissionService ?? new PermissionService(
            $this->companies,
            new ShareRepository(),
            $this->permissionValidator
        );
    }

    /**
     * List companies with filters.
     *
     * @agent-use: GET /api/companies
     * @agent-pattern: Standard list
     */
    public function list(array $filters = []): array
    {
        $validated = $this->validator->validateList($filters);
        return [
            'success' => true,
            'data' => $this->companies->list($validated),
        ];
    }

    /**
     * Create a new company.
     *
     * @agent-use: POST /api/companies
     * @agent-pattern: Validate -> repository
     */
    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $company = $this->companies->create($data);
        return ['success' => true, 'data' => $company];
    }

    /**
     * Update company attributes.
     *
     * @agent-use: PUT /api/companies/{id}
     * @agent-pattern: Update pattern
     */
    public function update(int $id, array $input): array
    {
        $data = $this->validator->validateUpdate($input);
        $company = $this->companies->update($id, $data);
        return ['success' => true, 'data' => $company];
    }

    /**
     * Assign permissions to a user/role for a company.
     *
     * @agent-use: POST /api/companies/{id}/permissions
     * @agent-pattern: ACL assignment
     */
    public function assignPermission(int $companyId, array $input, ?int $actorId = null): array
    {
        $payload = $this->validator->validateAssignPermission($input + ['company_id' => $companyId]);
        if ($actorId !== null) {
            $this->permissionService->assertCompanyAccess($actorId, $companyId, 'admin');
        }
        $perm = $this->companies->assignPermission($payload);
        return ['success' => true, 'data' => $perm];
    }

    /**
     * List company permissions (guarded by company scope).
     *
     * @agent-use: GET /api/companies/{id}/permissions
     * @agent-pattern: Guarded listing
     */
    public function permissions(int $companyId, ?int $actorId = null): array
    {
        if ($actorId !== null) {
            $this->permissionService->assertCompanyAccess($actorId, $companyId, 'read');
        }
        return ['success' => true, 'data' => $this->companies->listPermissions($companyId)];
    }
}
