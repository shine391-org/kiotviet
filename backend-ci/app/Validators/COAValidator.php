<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate chart of accounts.
 *
 * @agent-validator: COA
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class COAValidator
{
    private array $allowedTypes = ['asset', 'liability', 'equity', 'income', 'expense'];

    public function validateCreate(array $input): array
    {
        $code = trim((string) ($input['code'] ?? ''));
        if ($code === '') {
            throw new InvalidArgumentException('code is required');
        }
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('name is required');
        }

        $accountType = strtolower(trim((string) ($input['account_type'] ?? '')));
        if (! in_array($accountType, $this->allowedTypes, true)) {
            throw new InvalidArgumentException('invalid account_type');
        }

        $currency = $input['currency'] ?? null;
        if ($currency !== null) {
            $currency = strtoupper(trim((string) $currency));
            if ($currency === '') {
                $currency = null;
            }
        }

        return [
            'code' => $code,
            'name' => $name,
            'account_type' => $accountType,
            'currency' => $currency,
            'parent_id' => $this->normalizeId($input['parent_id'] ?? null),
            'is_group' => ! empty($input['is_group']) ? 1 : 0,
        ];
    }

    private function normalizeId($id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }
        $int = (int) $id;
        if ($int <= 0) {
            throw new InvalidArgumentException('parent_id must be positive');
        }
        return $int;
    }
}
