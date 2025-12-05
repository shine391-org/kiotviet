<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate quality inspections.
 *
 * @agent-validator: Quality inspections
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class QualityInspectionValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    /**
     * Validate inspection creation payload.
     * @agent-use: Create draft inspection
     * @agent-pattern: Nested item validation
     */
    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'reference_type' => 'required|string|max_length[80]',
            'reference_id' => 'required|integer|greater_than[0]',
            'inspected_by' => 'permit_empty|integer|greater_than[0]',
            'notes' => 'permit_empty|string',
        ]);

        $items = $input['items'] ?? null;
        if (! is_array($items) || empty($items)) {
            throw new InvalidArgumentException('At least one inspection item is required');
        }

        $normalizedItems = [];
        foreach ($items as $idx => $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Invalid inspection item at index ' . $idx);
            }
            if (! isset($item['parameter_id']) || ! is_numeric($item['parameter_id'])) {
                throw new InvalidArgumentException('parameter_id is required for item ' . $idx);
            }

            $valueNumeric = null;
            $valueText = null;
            if (array_key_exists('value_numeric', $item)) {
                $valueNumeric = $item['value_numeric'] === '' ? null : (float) $item['value_numeric'];
            }
            if (array_key_exists('value_text', $item)) {
                $valueText = $this->stringOrNull($item['value_text']);
            }
            if (array_key_exists('value', $item)) {
                $raw = $item['value'];
                if (is_numeric($raw)) {
                    $valueNumeric = (float) $raw;
                    $valueText = (string) $raw;
                } else {
                    $valueText = $this->stringOrNull($raw);
                }
            }

            if ($valueNumeric === null && $valueText === null) {
                throw new InvalidArgumentException('Value is required for parameter ' . $item['parameter_id']);
            }

            $normalizedItems[] = [
                'parameter_id' => (int) $item['parameter_id'],
                'value_numeric' => $valueNumeric,
                'value_text' => $valueText,
                'notes' => isset($item['notes']) ? $this->stringOrNull($item['notes']) : null,
            ];
        }

        $data['items'] = $normalizedItems;
        return $data;
    }

    /** Validate list filters. */
    public function validateList(array $input): array
    {
        return $this->run($input, [
            'reference_type' => 'permit_empty|string|max_length[80]',
            'reference_id' => 'permit_empty|integer|greater_than[0]',
            'status' => 'permit_empty|string|max_length[20]',
            'result' => 'permit_empty|string|max_length[20]',
        ]);
    }

    /** Validate submit payload (optional inspected_by override). */
    public function validateSubmit(array $input): array
    {
        $data = $this->run($input, [
            'inspected_by' => 'permit_empty|integer|greater_than[0]',
        ]);
        if (array_key_exists('inspected_by', $data)) {
            $data['inspected_by'] = (int) $data['inspected_by'];
        }
        return $data;
    }

    /** Validate approve payload. */
    public function validateApprove(array $input): array
    {
        $data = $this->run($input, [
            'approved_by' => 'required|integer|greater_than[0]',
        ]);
        return ['approved_by' => (int) $data['approved_by']];
    }

    /** Validate reject payload. */
    public function validateReject(array $input): array
    {
        $data = $this->run($input, [
            'rejected_by' => 'required|integer|greater_than[0]',
            'reason' => 'permit_empty|string|max_length[255]',
        ]);

        return [
            'rejected_by' => (int) $data['rejected_by'],
            'reason' => isset($data['reason']) ? $this->stringOrNull($data['reason']) : null,
        ];
    }

    private function stringOrNull($value): ?string
    {
        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return $this->v->getValidated();
    }
}
