<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate POS profile inputs.
 *
 * @agent-validator: POS profile
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class POSProfileValidator
{
    public function validateCreate(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('name is required');
        }
        $branchId = isset($input['branch_id']) ? (int) $input['branch_id'] : 0;
        if ($branchId <= 0) {
            throw new InvalidArgumentException('branch_id is required for POS profile');
        }

        $data = [
            'name' => $name,
            'user_id' => $this->positiveIntOrNull($input['user_id'] ?? null, 'user_id'),
            'role_id' => $this->positiveIntOrNull($input['role_id'] ?? null, 'role_id'),
            'price_list_id' => $this->positiveIntOrNull($input['price_list_id'] ?? null, 'price_list_id'),
            'tax_template_id' => $this->positiveIntOrNull($input['tax_template_id'] ?? null, 'tax_template_id'),
            'warehouse_id' => $this->positiveIntOrNull($input['warehouse_id'] ?? null, 'warehouse_id'),
            'branch_id' => $branchId,
            'company' => isset($input['company']) ? trim((string) $input['company']) : null,
            'allow_offline' => ! empty($input['allow_offline']),
            'require_shift' => array_key_exists('require_shift', $input) ? (bool) $input['require_shift'] : true,
            'credit_limit' => isset($input['credit_limit']) ? (float) $input['credit_limit'] : 0.0,
            'status' => $input['status'] ?? 'active',
        ];

        $methods = $this->normalizeMethods($input['payment_methods'] ?? []);
        return $data + ['payment_methods' => $methods];
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
        foreach (['user_id','role_id','price_list_id','tax_template_id','warehouse_id','branch_id'] as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = $this->positiveIntOrNull($input[$field], $field);
            }
        }
        if (array_key_exists('allow_offline', $input)) {
            $data['allow_offline'] = (bool) $input['allow_offline'];
        }
        if (array_key_exists('require_shift', $input)) {
            $data['require_shift'] = (bool) $input['require_shift'];
        }
        if (array_key_exists('credit_limit', $input)) {
            $credit = (float) $input['credit_limit'];
            if ($credit < 0) {
                throw new InvalidArgumentException('credit_limit must be >= 0');
            }
            $data['credit_limit'] = $credit;
        }
        if (isset($input['status'])) {
            $data['status'] = trim((string) $input['status']);
        }
        if (isset($input['company'])) {
            $data['company'] = trim((string) $input['company']);
        }

        $methods = null;
        if (array_key_exists('payment_methods', $input)) {
            $methods = $this->normalizeMethods($input['payment_methods']);
        }

        return $data + ['payment_methods' => $methods];
    }

    public function validateResolve(array $input): array
    {
        $userId = $this->positiveIntOrNull($input['user_id'] ?? null, 'user_id', false);
        $branchId = $this->positiveIntOrNull($input['branch_id'] ?? null, 'branch_id', false);
        $profileId = $this->positiveIntOrNull($input['profile_id'] ?? null, 'profile_id', true);
        if (! $userId && ! $profileId) {
            throw new InvalidArgumentException('user_id or profile_id is required to resolve POS profile');
        }
        return [
            'user_id' => $userId,
            'branch_id' => $branchId,
            'profile_id' => $profileId,
        ];
    }

    private function positiveIntOrNull($value, string $field, bool $allowNull = true): ?int
    {
        if ($value === null || $value === '') {
            if ($allowNull) {
                return null;
            }
            throw new InvalidArgumentException("{$field} is required");
        }
        $int = (int) $value;
        if ($int <= 0) {
            throw new InvalidArgumentException("{$field} must be positive");
        }
        return $int;
    }

    private function normalizeMethods($methods): array
    {
        if ($methods === null) {
            return [];
        }
        if (! is_array($methods)) {
            throw new InvalidArgumentException('payment_methods must be an array');
        }
        $normalized = [];
        foreach ($methods as $method) {
            if (is_string($method)) {
                $code = strtoupper(trim($method));
                if ($code === '') {
                    continue;
                }
                $normalized[] = ['payment_method' => $code, 'is_allowed' => true];
                continue;
            }
            if (! is_array($method)) {
                throw new InvalidArgumentException('payment_methods entries must be string or array');
            }
            $code = strtoupper(trim((string) ($method['payment_method'] ?? '')));
            if ($code === '') {
                throw new InvalidArgumentException('payment_method is required in payment_methods');
            }
            $normalized[] = [
                'payment_method' => $code,
                'is_allowed' => array_key_exists('is_allowed', $method) ? (bool) $method['is_allowed'] : true,
            ];
        }
        if (empty($normalized)) {
            throw new InvalidArgumentException('payment_methods must contain at least one method');
        }
        return $normalized;
    }
}
