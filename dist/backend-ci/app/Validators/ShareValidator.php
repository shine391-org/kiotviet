<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate document share payloads.
 *
 * @agent-validator: Share
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class ShareValidator
{
    private PermissionValidator $permissions;

    public function __construct(?PermissionValidator $permissions = null)
    {
        $this->permissions = $permissions ?? new PermissionValidator();
    }

    public function validateShare(array $input): array
    {
        $companyId = $this->permissions->positiveInt($input['company_id'] ?? null, 'company_id');
        $entityType = trim((string) ($input['entity_type'] ?? ''));
        $entityId = $this->permissions->positiveInt($input['entity_id'] ?? null, 'entity_id');
        if ($entityType === '') {
            throw new InvalidArgumentException('entity_type is required');
        }
        $userId = $input['shared_with_user_id'] ?? null;
        $role = isset($input['shared_with_role']) ? trim((string) $input['shared_with_role']) : null;
        if ($userId === null && $role === null) {
            throw new InvalidArgumentException('shared_with_user_id or shared_with_role is required');
        }

        return [
            'company_id' => $companyId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'shared_with_user_id' => $userId !== null ? $this->permissions->positiveInt($userId, 'shared_with_user_id') : null,
            'shared_with_role' => $role ?: null,
            'permissions' => $this->permissions->normalizePermissions($input['permissions'] ?? []),
            'expires_at' => $input['expires_at'] ?? null,
            'created_by' => isset($input['created_by']) ? $this->permissions->positiveInt($input['created_by'], 'created_by') : null,
        ];
    }

    public function validateListFilters(array $input): array
    {
        $filters = [];
        foreach (['company_id','entity_id','shared_with_user_id'] as $field) {
            if (! empty($input[$field])) {
                $filters[$field] = $this->permissions->positiveInt($input[$field], $field);
            }
        }
        if (! empty($input['entity_type'])) {
            $filters['entity_type'] = trim((string) $input['entity_type']);
        }
        return $filters;
    }

    public function validateUnshare(int $id): int
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('share id must be positive');
        }
        return $id;
    }
}
