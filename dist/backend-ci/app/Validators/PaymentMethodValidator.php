<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate payment method inputs.
 *
 * @agent-validator: Payment methods
 * @agent-pattern: Validation first
 * @agent-reusable: HIGH
 */
class PaymentMethodValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    /**
     * Validate list filters.
     *
     * @agent-use: GET /api/payment-methods
     * @agent-pattern: Standard list filters
     */
    public function validateListFilters(array $input): array
    {
        $data = array_merge(['page' => 1, 'limit' => 20], $input);

        $rules = [
            'page' => 'permit_empty|integer|greater_than_equal_to[1]',
            'limit' => 'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[200]',
            'search' => 'permit_empty|string|max_length[255]',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
        ];

        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid filters');
        }

        $validated = $this->v->getValidated();
        $validated['page'] = (int) ($validated['page'] ?? 1);
        $validated['limit'] = (int) ($validated['limit'] ?? 20);
        $validated['search'] = isset($validated['search']) ? trim((string) $validated['search']) : null;
        $validated['is_active'] = array_key_exists('is_active', $validated)
            ? (bool) filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN)
            : true; // default: only active

        return $validated;
    }

    /**
     * Validate create payload.
     *
     * @agent-use: POST /api/payment-methods
     * @agent-pattern: Standard create validation
     */
    public function validateCreate(array $input): array
    {
        $code = strtoupper(trim((string) ($input['code'] ?? '')));
        if ($code === '') {
            throw new InvalidArgumentException('Payment method code is required');
        }
        if (! preg_match('/^[A-Z_]+$/', $code)) {
            throw new InvalidArgumentException('Payment method code must be UPPERCASE alphanumeric with underscores');
        }

        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Payment method name is required');
        }
        if (strlen($name) > 255) {
            throw new InvalidArgumentException('Payment method name is too long (max 255 chars)');
        }

        $displayOrder = isset($input['display_order']) ? (int) $input['display_order'] : 0;
        if ($displayOrder < 0) {
            throw new InvalidArgumentException('Display order must be >= 0');
        }

        $payload = [
            'code' => $code,
            'name' => $name,
            'description' => isset($input['description']) ? trim((string) $input['description']) : null,
            'is_active' => array_key_exists('is_active', $input)
                ? (bool) filter_var($input['is_active'], FILTER_VALIDATE_BOOLEAN)
                : true,
            'display_order' => $displayOrder,
        ];

        if (isset($input['name_translations'])) {
            $translations = $this->normalizeTranslations($input['name_translations']);
            if (! empty($translations)) {
                $payload['name_translations'] = $translations;
            }
        }

        return $payload;
    }

    /**
     * Validate update payload.
     *
     * @agent-use: PUT /api/payment-methods/{id}
     * @agent-pattern: Partial update validation
     */
    public function validateUpdate(array $input): array
    {
        $data = [];

        if (array_key_exists('code', $input)) {
            $code = strtoupper(trim((string) $input['code']));
            if ($code === '') {
                throw new InvalidArgumentException('Payment method code is required');
            }
            if (! preg_match('/^[A-Z_]+$/', $code)) {
                throw new InvalidArgumentException('Payment method code must be UPPERCASE alphanumeric with underscores');
            }
            $data['code'] = $code;
        }

        if (array_key_exists('name', $input)) {
            $name = trim((string) $input['name']);
            if ($name === '') {
                throw new InvalidArgumentException('Payment method name is required');
            }
            if (strlen($name) > 255) {
                throw new InvalidArgumentException('Payment method name is too long (max 255 chars)');
            }
            $data['name'] = $name;
        }

        if (array_key_exists('description', $input)) {
            $data['description'] = $input['description'] === null ? null : trim((string) $input['description']);
        }

        if (array_key_exists('is_active', $input)) {
            $data['is_active'] = (bool) filter_var($input['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        if (array_key_exists('display_order', $input)) {
            $display = (int) $input['display_order'];
            if ($display < 0) {
                throw new InvalidArgumentException('Display order must be >= 0');
            }
            $data['display_order'] = $display;
        }

        if (array_key_exists('name_translations', $input)) {
            $translations = $this->normalizeTranslations($input['name_translations']);
            $data['name_translations'] = $translations ?: null;
        }

        if (empty($data)) {
            throw new InvalidArgumentException('No fields to update');
        }

        return $data;
    }

    /**
     * Normalize translation map (locale => text).
     */
    private function normalizeTranslations($translations): array
    {
        if (! is_array($translations)) {
            throw new InvalidArgumentException('name_translations must be an object/dictionary of locale => name');
        }

        $result = [];
        foreach ($translations as $locale => $value) {
            $localeKey = trim((string) $locale);
            $text = trim((string) $value);
            if ($localeKey === '' || $text === '') {
                continue;
            }
            if (strlen($text) > 255) {
                throw new InvalidArgumentException('Translation value is too long (max 255 chars)');
            }
            $result[$localeKey] = $text;
        }

        return $result;
    }
}
