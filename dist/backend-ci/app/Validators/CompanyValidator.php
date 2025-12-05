<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate company inputs and permission assignments.
 *
 * @agent-validator: Company
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class CompanyValidator
{
    private PermissionValidator $permissions;

    public function __construct(?PermissionValidator $permissions = null)
    {
        $this->permissions = $permissions ?? new PermissionValidator();
    }

    public function validateCreate(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $code = trim((string) ($input['code'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('name is required');
        }
        if ($code === '') {
            throw new InvalidArgumentException('code is required');
        }
        return [
            'name' => $name,
            'code' => strtoupper($code),
            'status' => $input['status'] ?? 'active',
            'is_default' => ! empty($input['is_default']) ? 1 : 0,
        ];
    }

    public function validateUpdate(array $input): array
    {
        $data = [];
        if (isset($input['name'])) {
            $data['name'] = trim((string) $input['name']);
            if ($data['name'] === '') {
                throw new InvalidArgumentException('name cannot be empty');
            }
        }
        if (isset($input['code'])) {
            $data['code'] = strtoupper(trim((string) $input['code']));
            if ($data['code'] === '') {
                throw new InvalidArgumentException('code cannot be empty');
            }
        }
        if (isset($input['status'])) {
            $data['status'] = trim((string) $input['status']);
        }
        if (isset($input['is_default'])) {
            $data['is_default'] = ! empty($input['is_default']) ? 1 : 0;
        }
        if (empty($data)) {
            throw new InvalidArgumentException('No fields to update');
        }
        return $data;
    }

    public function validateAssignPermission(array $input): array
    {
        $companyId = $this->permissions->positiveInt($input['company_id'] ?? null, 'company_id');
        $userId = $input['user_id'] ?? null;
        $roleName = isset($input['role_name']) ? trim((string) $input['role_name']) : null;
        if ($userId === null && $roleName === null) {
            throw new InvalidArgumentException('user_id or role_name is required to assign permission');
        }
        $payload = [
            'company_id' => $companyId,
            'user_id' => $userId !== null ? $this->permissions->positiveInt($userId, 'user_id') : null,
            'role_name' => $roleName ?: null,
            'permissions' => $this->permissions->normalizePermissions($input['permissions'] ?? []),
        ];
        return $payload;
    }

    public function validateList(array $filters): array
    {
        return [
            'status' => isset($filters['status']) ? trim((string) $filters['status']) : null,
            'is_default' => ! empty($filters['is_default']),
        ];
    }
}
