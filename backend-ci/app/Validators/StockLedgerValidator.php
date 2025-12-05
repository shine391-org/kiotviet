<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate stock ledger entries.
 *
 * @agent-validator: Stock ledger
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class StockLedgerValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateRecord(array $input): array
    {
        $data = $this->run($input, [
            'product_id' => 'required|integer|greater_than[0]',
            'variant_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'branch_id' => 'required|integer|greater_than[0]',
            'warehouse_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'batch_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'serial_number' => 'permit_empty|string|max_length[160]',
            'movement_date' => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'reference_type' => 'required|string|max_length[80]',
            'reference_id' => 'required|integer|greater_than[0]',
            'reference_seq' => 'permit_empty|integer|greater_than_equal_to[1]',
            'qty_delta' => 'required|numeric',
            'unit_cost' => 'permit_empty|numeric',
            'total_cost' => 'permit_empty|numeric',
        ]);
        $data['movement_date'] = $data['movement_date'] ?? date('Y-m-d H:i:s');
        $data['reference_seq'] = $data['reference_seq'] ?? 1;
        $data['unit_cost'] = isset($data['unit_cost']) ? (float) $data['unit_cost'] : 0;
        $data['total_cost'] = isset($data['total_cost']) ? (float) $data['total_cost'] : $data['unit_cost'] * $data['qty_delta'];
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
