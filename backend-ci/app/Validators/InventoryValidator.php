<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/** Inventory validation @agent-validator: Inventory @agent-pattern: Validation first @agent-reusable: MEDIUM */
class InventoryValidator
{
    protected Validation $v;
    public function __construct(?Validation $v = null) { $this->v = $v ?? Services::validation(null, false); }

    /** Validate warehouse create. @agent-use: POST /api/warehouses @agent-pattern: Standard create */
    public function validateWarehouseCreate(array $input): array
    {
        $rules = [
            'code'   => 'required|string|max_length[50]',
            'name'   => 'required|string|max_length[255]',
            'address'=> 'permit_empty|string',
            'phone'  => 'permit_empty|string|max_length[20]',
            'manager_id' => 'permit_empty|integer',
            'status' => 'permit_empty|in_list[active,inactive]',
            'is_default' => 'permit_empty|in_list[0,1,true,false]',
        ];
        return $this->normalizeBools($this->run($input, $rules), ['is_default']);
    }

    /** Validate warehouse update. @agent-use: PUT /api/warehouses/{id} @agent-pattern: Standard update */
    public function validateWarehouseUpdate(array $input): array
    {
        $validated = $this->normalizeBools($this->run($input, [
            'code'   => 'permit_empty|string|max_length[50]',
            'name'   => 'permit_empty|string|max_length[255]',
            'address'=> 'permit_empty|string',
            'phone'  => 'permit_empty|string|max_length[20]',
            'manager_id' => 'permit_empty|integer',
            'status' => 'permit_empty|in_list[active,inactive]',
            'is_default' => 'permit_empty|in_list[0,1,true,false]',
        ]), ['is_default']);
        if (empty($validated)) { throw new InvalidArgumentException('No data to update'); }
        return $validated;
    }

    /** Validate movement create. @agent-use: POST /api/inventory/movements @agent-pattern: Movement validation */
    public function validateMovement(array $input): array
    {
        $rules = [
            'movement_type' => 'required|in_list[IN,OUT,TRANSFER,ADJUSTMENT]',
            'product_id'    => 'required|integer|greater_than[0]',
            'variant_id'    => 'permit_empty|integer|greater_than_equal_to[0]',
            'from_warehouse_id' => 'permit_empty|integer|greater_than[0]',
            'to_warehouse_id'   => 'permit_empty|integer|greater_than[0]',
            'quantity'      => 'required|numeric|greater_than[0]',
            'unit_cost'     => 'permit_empty|numeric',
            'reason'        => 'permit_empty|string|max_length[255]',
            'reference_code'=> 'permit_empty|string|max_length[50]',
            'created_by'    => 'permit_empty|integer',
            'valuation_method' => 'permit_empty|in_list[FIFO,LIFO,AVERAGE]',
            'batch_id'      => 'permit_empty|integer|greater_than_equal_to[0]',
            'serial_number' => 'permit_empty|string|max_length[160]',
        ];
        $data = $this->run($input, $rules);

        // Validate business rules based on movement_type
        $type = $data['movement_type'];
        if ($type === 'TRANSFER') {
            if (empty($data['from_warehouse_id']) || empty($data['to_warehouse_id'])) {
                throw new InvalidArgumentException('TRANSFER requires both from_warehouse_id and to_warehouse_id');
            }
        } elseif ($type === 'IN') {
            if (empty($data['to_warehouse_id'])) {
                throw new InvalidArgumentException('IN requires to_warehouse_id');
            }
        } elseif ($type === 'OUT') {
            if (empty($data['from_warehouse_id'])) {
                throw new InvalidArgumentException('OUT requires from_warehouse_id');
            }
        }

        $data['unit_cost'] = $data['unit_cost'] ?? 0;
        $data['created_by'] = $data['created_by'] ?? 0;
        $data['valuation_method'] = $data['valuation_method'] ?? 'AVERAGE';
        return $data;
    }

    /** Validate reservation/reservation release. */
    public function validateReservation(array $input): array
    {
        $rules = [
            'product_id' => 'required|integer|greater_than[0]',
            'variant_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'warehouse_id' => 'required|integer|greater_than[0]',
            'quantity' => 'required|numeric|greater_than[0]',
            'reference' => 'permit_empty|string|max_length[100]',
        ];
        return $this->run($input, $rules);
    }

    /** Validate alert action (resolve/ignore). */
    public function validateAlertAction(array $input): array
    {
        $rules = [
            'alert_id' => 'required|integer|greater_than[0]',
            'resolved_by' => 'permit_empty|integer|greater_than_equal_to[0]',
        ];
        return $this->run($input, $rules);
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return $this->v->getValidated();
    }

    private function normalizeBools(array $data, array $fields): array
    {
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $data[$f] = filter_var($data[$f], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }
        }
        return $data;
    }
}
