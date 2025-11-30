<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate assets.
 *
 * @agent-validator: Asset
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class AssetValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'asset_name' => 'required|string|max_length[255]',
            'category' => 'permit_empty|string|max_length[120]',
            'purchase_date' => 'permit_empty|valid_date[Y-m-d]',
            'cost' => 'permit_empty|decimal',
            'location' => 'permit_empty|string|max_length[255]',
            'status' => 'permit_empty|string|max_length[30]',
            'salvage_value' => 'permit_empty|decimal',
            'useful_life_months' => 'permit_empty|integer|greater_than_equal_to[0]',
            'created_by' => 'permit_empty|integer|greater_than_equal_to[0]',
        ]);
        $data['status'] = $data['status'] ?? 'draft';
        $data['cost'] = isset($data['cost']) ? (float) $data['cost'] : 0.0;
        $data['salvage_value'] = isset($data['salvage_value']) ? (float) $data['salvage_value'] : 0.0;
        $data['useful_life_months'] = isset($data['useful_life_months']) ? (int) $data['useful_life_months'] : 0;
        return $data;
    }

    public function validateStatus(array $input): array
    {
        $data = $this->run($input, [
            'status' => 'required|string|max_length[30]',
        ]);
        $this->assertStatus($data['status']);
        return $data;
    }

    private function assertStatus(string $status): void
    {
        $allowed = ['draft', 'active', 'disposed', 'maintenance', 'retired'];
        if (! in_array($status, $allowed, true)) {
            throw new InvalidArgumentException('Invalid asset status');
        }
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return $this->v->getValidated();
    }
}
