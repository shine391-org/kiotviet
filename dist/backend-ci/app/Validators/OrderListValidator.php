<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate order listing filters.
 *
 * @agent-validator: Order list filters
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class OrderListValidator
{
    /**
     * Validate and normalize filters for listing orders.
     *
     * @agent-use: Orders listing
     * @agent-pattern: Sanitize + defaults
     */
    public function validate(array $input): array
    {
        $filters = [];

        // Pagination
        $page = isset($input['page']) ? (int) $input['page'] : 1;
        $limit = isset($input['limit']) ? (int) $input['limit'] : 25;
        if ($page < 1) {
            throw new InvalidArgumentException('page must be >= 1');
        }
        if ($limit < 1 || $limit > 200) {
            throw new InvalidArgumentException('limit must be between 1 and 200');
        }
        $filters['page'] = $page;
        $filters['limit'] = $limit;

        // Search
        $search = isset($input['search']) ? trim((string) $input['search']) : '';
        $filters['search'] = $search !== '' ? $search : null;

        // Branch
        if (isset($input['branch_id']) && $input['branch_id'] !== '') {
            $branchId = (int) $input['branch_id'];
            if ($branchId < 1) {
                throw new InvalidArgumentException('branch_id must be positive');
            }
            $filters['branch_id'] = $branchId;
        }

        // Status can be scalar or array
        if (isset($input['status'])) {
            $status = $input['status'];
            if (is_array($status)) {
                $status = array_filter(array_map('strval', $status));
            } else {
                $status = trim((string) $status);
                $status = $status === '' ? [] : [$status];
            }
            $filters['status'] = $status;
        } else {
            $filters['status'] = [];
        }

        // Date filters (order_date)
        $filters['date_from'] = isset($input['date_from']) ? $this->assertDate($input['date_from'], 'date_from') : null;
        $filters['date_to'] = isset($input['date_to']) ? $this->assertDate($input['date_to'], 'date_to') : null;
        if ($filters['date_from'] && $filters['date_to'] && $filters['date_from'] > $filters['date_to']) {
            throw new InvalidArgumentException('date_from must be before date_to');
        }

        // Payment method filter
        if (isset($input['payment_method']) && $input['payment_method'] !== '') {
            $filters['payment_method'] = trim((string) $input['payment_method']);
        }

        return $filters;
    }

    private function assertDate(string $value, string $field): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (! preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $value)) {
            throw new InvalidArgumentException($field . ' must be YYYY-MM-DD');
        }
        return $value;
    }
}
