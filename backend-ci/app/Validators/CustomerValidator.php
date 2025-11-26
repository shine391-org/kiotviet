<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate customer inputs.
 *
 * @agent-validator: Customers
 * @agent-pattern: Validation first
 * @agent-reusable: HIGH
 */
class CustomerValidator
{
    private const TYPES = ['INDIVIDUAL', 'COMPANY', 'HOUSEHOLD'];
    private const GENDERS = ['MALE', 'FEMALE', 'OTHER'];

    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    /**
     * Validate list filters (pagination + search).
     *
     * @agent-use: GET /api/customers
     * @agent-pattern: Standard list filters
     */
    public function validateListFilters(array $input): array
    {
        $data = array_merge(['page' => 1, 'limit' => 20], $input);

        $rules = [
            'page' => 'permit_empty|integer|greater_than_equal_to[1]',
            'limit' => 'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[200]',
            'search' => 'permit_empty|string|max_length[255]',
            'customer_type' => 'permit_empty|string|max_length[50]',
            'gender' => 'permit_empty|string|max_length[20]',
        ];

        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid filters');
        }

        $validated = $this->v->getValidated();
        $validated['page'] = (int) ($validated['page'] ?? 1);
        $validated['limit'] = (int) ($validated['limit'] ?? 20);
        $validated['search'] = isset($validated['search']) ? trim((string) $validated['search']) : null;

        if (isset($validated['customer_type'])) {
            $validated['customer_type'] = $this->normalizeType($validated['customer_type']);
        }
        if (isset($validated['gender'])) {
            $validated['gender'] = $this->normalizeGender($validated['gender']);
        }

        return $validated;
    }

    /**
     * Validate create payload.
     *
     * @agent-use: POST /api/customers
     * @agent-pattern: Standard create validation
     */
    public function validateCreate(array $input): array
    {
        $name = $this->requiredString($input['name'] ?? '', 255, 'Customer name is required');

        $data = [
            'organization_id' => isset($input['organization_id']) ? $this->positiveInt($input['organization_id'], 'organization_id', allowZero: false) : 1,
            'customer_group_id' => array_key_exists('customer_group_id', $input) && $input['customer_group_id'] !== null
                ? $this->positiveInt($input['customer_group_id'], 'customer_group_id')
                : null,
            'name' => $name,
            'customer_type' => $this->normalizeType($input['customer_type'] ?? 'INDIVIDUAL'),
        ];

        $data['email'] = $this->optionalEmail($input['email'] ?? null, 'email');
        $data['phone'] = $this->optionalPhone($input['phone'] ?? null, 'phone');
        $data['phone2'] = $this->optionalPhone($input['phone2'] ?? null, 'phone2');
        $data['gender'] = $this->optionalGender($input['gender'] ?? null);
        $data['facebook'] = $this->optionalUrl($input['facebook'] ?? null, 'facebook', 255);
        $data['company_name'] = $this->optionalString($input['company_name'] ?? null, 255);
        $data['tax_code'] = $this->optionalTaxCode($input['tax_code'] ?? null);
        $data['buyer_name'] = $this->optionalString($input['buyer_name'] ?? null, 255);
        $data['invoice_company_name'] = $this->optionalString($input['invoice_company_name'] ?? null, 255);
        $data['invoice_address'] = $this->optionalString($input['invoice_address'] ?? null, 500);
        $data['invoice_province'] = $this->optionalString($input['invoice_province'] ?? null, 120);
        $data['invoice_district'] = $this->optionalString($input['invoice_district'] ?? null, 120);
        $data['invoice_ward'] = $this->optionalString($input['invoice_ward'] ?? null, 120);
        $data['invoice_email'] = $this->optionalEmail($input['invoice_email'] ?? null, 'invoice_email');
        $data['invoice_phone'] = $this->optionalPhone($input['invoice_phone'] ?? null, 'invoice_phone');
        $data['cccd_cmnd'] = $this->optionalString($input['cccd_cmnd'] ?? null, 50);
        $data['id_number'] = $this->optionalString($input['id_number'] ?? null, 50);
        $data['bank_account'] = $this->optionalString($input['bank_account'] ?? null, 50);
        $data['bank_name'] = $this->optionalString($input['bank_name'] ?? null, 255);
        $data['notes'] = $this->optionalText($input['notes'] ?? null);

        return $data;
    }

    /**
     * Validate update payload (partial).
     *
     * @agent-use: PUT /api/customers/{id}
     * @agent-pattern: Partial update validation
     */
    public function validateUpdate(array $input): array
    {
        $data = [];

        if (array_key_exists('name', $input)) {
            $data['name'] = $this->requiredString($input['name'], 255, 'Customer name is required');
        }
        if (array_key_exists('customer_group_id', $input)) {
            $data['customer_group_id'] = $input['customer_group_id'] === null
                ? null
                : $this->positiveInt($input['customer_group_id'], 'customer_group_id');
        }
        if (array_key_exists('customer_type', $input)) {
            $data['customer_type'] = $this->normalizeType($input['customer_type']);
        }
        if (array_key_exists('gender', $input)) {
            $data['gender'] = $this->optionalGender($input['gender']);
        }
        if (array_key_exists('email', $input)) {
            $data['email'] = $this->optionalEmail($input['email'], 'email');
        }
        if (array_key_exists('phone', $input)) {
            $data['phone'] = $this->optionalPhone($input['phone'], 'phone');
        }
        if (array_key_exists('phone2', $input)) {
            $data['phone2'] = $this->optionalPhone($input['phone2'], 'phone2');
        }
        if (array_key_exists('facebook', $input)) {
            $data['facebook'] = $this->optionalUrl($input['facebook'], 'facebook', 255);
        }
        if (array_key_exists('company_name', $input)) {
            $data['company_name'] = $this->optionalString($input['company_name'], 255);
        }
        if (array_key_exists('tax_code', $input)) {
            $data['tax_code'] = $this->optionalTaxCode($input['tax_code']);
        }
        if (array_key_exists('buyer_name', $input)) {
            $data['buyer_name'] = $this->optionalString($input['buyer_name'], 255);
        }
        if (array_key_exists('invoice_company_name', $input)) {
            $data['invoice_company_name'] = $this->optionalString($input['invoice_company_name'], 255);
        }
        if (array_key_exists('invoice_address', $input)) {
            $data['invoice_address'] = $this->optionalString($input['invoice_address'], 500);
        }
        if (array_key_exists('invoice_province', $input)) {
            $data['invoice_province'] = $this->optionalString($input['invoice_province'], 120);
        }
        if (array_key_exists('invoice_district', $input)) {
            $data['invoice_district'] = $this->optionalString($input['invoice_district'], 120);
        }
        if (array_key_exists('invoice_ward', $input)) {
            $data['invoice_ward'] = $this->optionalString($input['invoice_ward'], 120);
        }
        if (array_key_exists('invoice_email', $input)) {
            $data['invoice_email'] = $this->optionalEmail($input['invoice_email'], 'invoice_email');
        }
        if (array_key_exists('invoice_phone', $input)) {
            $data['invoice_phone'] = $this->optionalPhone($input['invoice_phone'], 'invoice_phone');
        }
        if (array_key_exists('cccd_cmnd', $input)) {
            $data['cccd_cmnd'] = $this->optionalString($input['cccd_cmnd'], 50);
        }
        if (array_key_exists('id_number', $input)) {
            $data['id_number'] = $this->optionalString($input['id_number'], 50);
        }
        if (array_key_exists('bank_account', $input)) {
            $data['bank_account'] = $this->optionalString($input['bank_account'], 50);
        }
        if (array_key_exists('bank_name', $input)) {
            $data['bank_name'] = $this->optionalString($input['bank_name'], 255);
        }
        if (array_key_exists('notes', $input)) {
            $data['notes'] = $this->optionalText($input['notes']);
        }

        if (empty($data)) {
            throw new InvalidArgumentException('No fields to update');
        }

        return $data;
    }

    private function requiredString($value, int $max, string $message): string
    {
        $text = trim((string) $value);
        if ($text === '') {
            throw new InvalidArgumentException($message);
        }
        if (strlen($text) > $max) {
            throw new InvalidArgumentException($message . " (max {$max} chars)");
        }
        return $text;
    }

    private function optionalString($value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }
        if (strlen($text) > $max) {
            throw new InvalidArgumentException("Value too long (max {$max} chars)");
        }
        return $text;
    }

    private function optionalText($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }

    private function optionalEmail($value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $email = trim((string) $value);
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("{$field} is not a valid email");
        }
        if (strlen($email) > 255) {
            throw new InvalidArgumentException("{$field} is too long (max 255 chars)");
        }
        return $email;
    }

    private function optionalPhone($value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $phone = trim((string) $value);
        if (! preg_match('/^[0-9+\\-()\\s]{6,20}$/', $phone)) {
            throw new InvalidArgumentException("{$field} must be 6-20 digits/characters");
        }
        return $phone;
    }

    private function optionalUrl($value, string $field, int $max): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $url = trim((string) $value);
        if (strlen($url) > $max) {
            throw new InvalidArgumentException("{$field} is too long (max {$max} chars)");
        }
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("{$field} must be a valid URL");
        }
        return $url;
    }

    private function optionalTaxCode($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $tax = trim((string) $value);
        if (! preg_match('/^[0-9]{10,14}$/', $tax)) {
            throw new InvalidArgumentException('tax_code must be 10-14 digits');
        }
        return $tax;
    }

    private function optionalGender($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return $this->normalizeGender($value);
    }

    private function normalizeGender(string $gender): string
    {
        $g = strtoupper(trim($gender));
        if (! in_array($g, self::GENDERS, true)) {
            throw new InvalidArgumentException('gender must be one of: ' . implode(',', self::GENDERS));
        }
        return $g;
    }

    private function normalizeType(string $type): string
    {
        $t = strtoupper(trim($type));
        if (! in_array($t, self::TYPES, true)) {
            throw new InvalidArgumentException('customer_type must be one of: ' . implode(',', self::TYPES));
        }
        return $t;
    }

    private function positiveInt($value, string $field, bool $allowZero = true): int
    {
        $int = (int) $value;
        if ($allowZero ? $int < 0 : $int <= 0) {
            throw new InvalidArgumentException("{$field} must be a positive integer");
        }
        return $int;
    }
}
