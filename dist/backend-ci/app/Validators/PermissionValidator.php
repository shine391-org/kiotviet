<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate permission payloads and required scopes.
 *
 * @agent-validator: Permission
 * @agent-pattern: Permission whitelist validator
 * @agent-reusable: HIGH
 */
class PermissionValidator
{
    public const ALLOWED = ['read', 'write', 'share', 'admin'];

    public function normalizePermissions($input): array
    {
        if ($input === null) {
            return [];
        }
        if (is_string($input)) {
            $input = array_filter(array_map('trim', explode(',', strtolower($input))));
        }
        if (! is_array($input)) {
            throw new InvalidArgumentException('permissions must be an array or comma-separated string');
        }
        $normalized = [];
        foreach ($input as $perm) {
            $value = strtolower(trim((string) $perm));
            if ($value === '') {
                continue;
            }
            if (! in_array($value, self::ALLOWED, true)) {
                throw new InvalidArgumentException("Invalid permission: {$value}");
            }
            $normalized[$value] = true;
        }
        if (empty($normalized)) {
            throw new InvalidArgumentException('permissions must contain at least one entry');
        }
        return array_keys($normalized);
    }

    public function validateRequired(?string $permission): ?string
    {
        if ($permission === null || $permission === '') {
            return null;
        }
        $perm = strtolower(trim($permission));
        if (! in_array($perm, self::ALLOWED, true)) {
            throw new InvalidArgumentException("Invalid permission requested: {$perm}");
        }
        return $perm;
    }

    public function positiveInt($value, string $field): int
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException("{$field} is required");
        }
        $int = (int) $value;
        if ($int <= 0) {
            throw new InvalidArgumentException("{$field} must be positive");
        }
        return $int;
    }
}
