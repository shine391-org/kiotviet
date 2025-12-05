<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate quality parameters.
 *
 * @agent-validator: Quality parameters
 * @agent-pattern: Validation first
 * @agent-reusable: HIGH
 */
class QualityParameterValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    /** Validate create payload. @agent-use: Create quality parameter */
    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'name' => 'required|string|max_length[150]',
            'uom' => 'permit_empty|string|max_length[50]',
            'min_value' => 'permit_empty|numeric',
            'max_value' => 'permit_empty|numeric',
            'specification' => 'permit_empty|string|max_length[255]',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
        ]);

        return $this->normalize($data, true);
    }

    /** Validate update payload. @agent-use: Update quality parameter */
    public function validateUpdate(array $input): array
    {
        $data = $this->run($input, [
            'name' => 'permit_empty|string|max_length[150]',
            'uom' => 'permit_empty|string|max_length[50]',
            'min_value' => 'permit_empty|numeric',
            'max_value' => 'permit_empty|numeric',
            'specification' => 'permit_empty|string|max_length[255]',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
        ]);

        if (empty($data)) {
            throw new InvalidArgumentException('No data to update');
        }

        return $this->normalize($data, false);
    }

    /** Validate list filters. */
    public function validateFilters(array $input): array
    {
        $data = $this->run($input, [
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
            'search' => 'permit_empty|string|max_length[150]',
        ]);

        return $this->normalize($data, false);
    }

    private function normalize(array $data, bool $forCreate): array
    {
        foreach (['min_value', 'max_value'] as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $data[$key] === '' ? null : (float) $data[$key];
            }
        }

        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        } elseif ($forCreate) {
            $data['is_active'] = true;
        }

        $min = $data['min_value'] ?? null;
        $max = $data['max_value'] ?? null;
        if ($min !== null && $max !== null && $max < $min) {
            throw new InvalidArgumentException('max_value must be greater than or equal to min_value');
        }

        return $data;
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return $this->v->getValidated();
    }
}
