<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate product batch payloads.
 *
 * @agent-validator: Product batch
 * @agent-pattern: Validation first
 * @agent-reusable: HIGH
 */
class ProductBatchValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    /** Validate batch creation. @agent-use: POST /api/product-batches */
    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'product_id' => 'required|integer|greater_than[0]',
            'variant_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'branch_id' => 'required|integer|greater_than[0]',
            'warehouse_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'batch_number' => 'required|string|max_length[120]',
            'manufacture_date' => 'permit_empty|valid_date[Y-m-d]',
            'expiry_date' => 'permit_empty|valid_date[Y-m-d]',
            'initial_quantity' => 'permit_empty|numeric',
            'current_quantity' => 'permit_empty|numeric',
            'cost_per_unit' => 'permit_empty|numeric',
            'supplier_name' => 'permit_empty|string|max_length[255]',
            'reference_document' => 'permit_empty|string|max_length[160]',
            'status' => 'permit_empty|in_list[active,inactive,closed]',
        ]);
        $data['warehouse_id'] = $data['warehouse_id'] ?? $data['branch_id'];
        $data['initial_quantity'] = isset($data['initial_quantity']) ? (float) $data['initial_quantity'] : 0.0;
        $data['current_quantity'] = isset($data['current_quantity']) ? (float) $data['current_quantity'] : $data['initial_quantity'];
        $data['cost_per_unit'] = isset($data['cost_per_unit']) ? (float) $data['cost_per_unit'] : 0.0;
        $data['status'] = $data['status'] ?? 'active';
        $this->assertDateOrder($data);
        return $data;
    }

    /** Validate batch update. @agent-use: PUT /api/product-batches/{id} */
    public function validateUpdate(array $input): array
    {
        $data = $this->run($input, [
            'variant_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'branch_id' => 'permit_empty|integer|greater_than[0]',
            'warehouse_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'batch_number' => 'permit_empty|string|max_length[120]',
            'manufacture_date' => 'permit_empty|valid_date[Y-m-d]',
            'expiry_date' => 'permit_empty|valid_date[Y-m-d]',
            'cost_per_unit' => 'permit_empty|numeric',
            'supplier_name' => 'permit_empty|string|max_length[255]',
            'reference_document' => 'permit_empty|string|max_length[160]',
            'status' => 'permit_empty|in_list[active,inactive,closed]',
        ]);
        if (empty($data)) {
            throw new InvalidArgumentException('No data to update');
        }
        $this->assertDateOrder($data);
        if (isset($data['warehouse_id']) && ! isset($data['branch_id']) && isset($input['branch_id'])) {
            $data['branch_id'] = (int) $input['branch_id'];
        }
        return $data;
    }

    /** Validate quantity adjustment. @agent-use: POST /api/product-batches/{id}/adjust-quantity */
    public function validateAdjust(array $input): array
    {
        $data = $this->run($input, [
            'quantity_delta' => 'required|numeric',
            'reason' => 'permit_empty|string|max_length[255]',
            'reference_type' => 'permit_empty|string|max_length[50]',
            'reference_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'branch_id' => 'permit_empty|integer|greater_than[0]',
            'warehouse_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'serial_number' => 'permit_empty|string|max_length[255]',
        ]);
        $delta = (float) $data['quantity_delta'];
        if (abs($delta) < 0.00001) {
            throw new InvalidArgumentException('quantity_delta must not be zero');
        }
        $data['quantity_delta'] = $delta;
        return $data;
    }

    /** Validate list filters including expiring-in-days. */
    public function validateListFilters(array $filters): array
    {
        $data = $this->run($filters, [
            'product_id' => 'permit_empty|integer|greater_than[0]',
            'variant_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'status' => 'permit_empty|string|max_length[30]',
            'search' => 'permit_empty|string|max_length[120]',
            'expiring_in_days' => 'permit_empty|integer|greater_than_equal_to[0]',
        ]);
        return $data;
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return $this->v->getValidated();
    }

    private function assertDateOrder(array $data): void
    {
        if (! empty($data['manufacture_date']) && ! empty($data['expiry_date'])) {
            if (strtotime($data['expiry_date']) < strtotime($data['manufacture_date'])) {
                throw new InvalidArgumentException('expiry_date must be after manufacture_date');
            }
        }
    }
}
