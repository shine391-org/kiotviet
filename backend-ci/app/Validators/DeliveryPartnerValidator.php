<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Delivery Partner Validator.
 * @agent-validator: DeliveryPartners
 */
class DeliveryPartnerValidator
{
    /** Validate list filters. */
    public function validateListFilters(array $filters): array
    {
        return [
            'page' => max(1, (int) ($filters['page'] ?? 1)),
            'limit' => min(100, max(1, (int) ($filters['limit'] ?? 20))),
            'search' => trim($filters['search'] ?? ''),
            'status' => $filters['status'] ?? null,
            'group_name' => $filters['group_name'] ?? null,
            'sort_by' => in_array($filters['sort_by'] ?? '', ['code', 'name', 'created_at', 'debt_amount']) 
                ? $filters['sort_by'] 
                : 'created_at',
            'sort_order' => in_array(strtolower($filters['sort_order'] ?? ''), ['asc', 'desc']) 
                ? $filters['sort_order'] 
                : 'desc',
        ];
    }

    /** Validate create payload. */
    public function validateCreate(array $data): array
    {
        $errors = [];

        // Name is required
        if (empty($data['name'])) {
            $errors[] = 'Partner name is required';
        }

        // Code - auto generate if not provided
        if (empty($data['code'])) {
            $data['code'] = $this->generateCode();
        }

        // Phone validation
        if (! empty($data['phone'])) {
            $phone = preg_replace('/\D/', '', $data['phone']);
            if (strlen($phone) < 9 || strlen($phone) > 15) {
                $errors[] = 'Invalid phone number';
            }
        }

        // Email validation
        if (! empty($data['email']) && ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        // Group name (partner type)
        $validGroups = ['individual', 'company', 'staff'];
        if (! empty($data['group_name']) && ! in_array($data['group_name'], $validGroups)) {
            $errors[] = 'Invalid group_name. Must be: individual, company, or staff';
        }

        if (! empty($errors)) {
            throw new InvalidArgumentException(implode('; ', $errors));
        }

        return $data;
    }

    /** Validate update payload. */
    public function validateUpdate(array $data): array
    {
        $errors = [];

        // Phone validation
        if (isset($data['phone']) && ! empty($data['phone'])) {
            $phone = preg_replace('/\D/', '', $data['phone']);
            if (strlen($phone) < 9 || strlen($phone) > 15) {
                $errors[] = 'Invalid phone number';
            }
        }

        // Email validation
        if (isset($data['email']) && ! empty($data['email']) && ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        // Group name validation
        $validGroups = ['individual', 'company', 'staff'];
        if (isset($data['group_name']) && ! empty($data['group_name']) && ! in_array($data['group_name'], $validGroups)) {
            $errors[] = 'Invalid group_name. Must be: individual, company, or staff';
        }

        if (! empty($errors)) {
            throw new InvalidArgumentException(implode('; ', $errors));
        }

        return $data;
    }

    private function generateCode(): string
    {
        // Will be replaced by repo->nextCode() in service
        return 'DT' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }
}
