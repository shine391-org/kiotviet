<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate regional tax payloads.
 *
 * @agent-validator: Regional tax
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class RegionalTaxValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateRule(array $input): array
    {
        $data = $this->run($input, [
            'country' => 'required|string|max_length[5]',
            'rule_json' => 'required',
        ]);
        if (! is_array($data['rule_json'])) {
            throw new InvalidArgumentException('rule_json must be array');
        }
        return $data;
    }

    public function validatePreview(array $input): array
    {
        $data = $this->run($input, [
            'country' => 'required|string|max_length[5]',
            'lines' => 'required',
        ]);
        $lines = $input['lines'] ?? [];
        if (! is_array($lines) || empty($lines)) {
            throw new InvalidArgumentException('lines required');
        }
        $normalized = [];
        foreach ($lines as $line) {
            $amount = isset($line['amount']) ? (float) $line['amount'] : 0;
            $taxRate = isset($line['tax_rate']) ? (float) $line['tax_rate'] : 0;
            if ($amount <= 0) {
                throw new InvalidArgumentException('line amount must be > 0');
            }
            $normalized[] = [
                'amount' => $amount,
                'tax_rate' => $taxRate,
            ];
        }
        $data['lines'] = $normalized;
        return $data;
    }

    public function validateCertificate(array $input): array
    {
        $data = $this->run($input, [
            'country' => 'required|string|max_length[5]',
            'party_type' => 'permit_empty|string|max_length[60]',
            'party_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'base_amount' => 'required|decimal',
            'withheld_rate' => 'required|decimal',
            'issue_date' => 'permit_empty|valid_date[Y-m-d]',
        ]);
        $data['base_amount'] = (float) $data['base_amount'];
        $data['withheld_rate'] = (float) $data['withheld_rate'];
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
