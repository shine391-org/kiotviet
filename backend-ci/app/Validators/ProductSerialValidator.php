<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate product serial payloads.
 *
 * @agent-validator: Product serial number
 * @agent-pattern: Validation first
 * @agent-reusable: HIGH
 */
class ProductSerialValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    /** Validate serial creation. @agent-use: POST /api/product-serials */
    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'product_id' => 'required|integer|greater_than[0]',
            'variant_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'batch_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'serial_number' => 'required|string|max_length[160]',
            'status' => 'permit_empty|in_list[available,reserved,sold,returned,damaged]',
            'warranty_expiry_date' => 'permit_empty|valid_date[Y-m-d]',
        ]);
        $data['status'] = $data['status'] ?? 'available';
        return $data;
    }

    /** Validate filters for listing. */
    public function validateFilters(array $filters): array
    {
        return $this->run($filters, [
            'product_id' => 'permit_empty|integer|greater_than[0]',
            'variant_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'batch_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'status' => 'permit_empty|string|max_length[30]',
            'order_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'search' => 'permit_empty|string|max_length[160]',
        ]);
    }

    /** Validate reservation request. @agent-use: POST /api/product-serials/reserve */
    public function validateReserve(array $input): array
    {
        $serials = $this->serialsFromInput($input);
        $data = $this->run($input, [
            'order_id' => 'required|integer|greater_than[0]',
        ]);
        if (empty($serials)) {
            throw new InvalidArgumentException('serial_numbers is required');
        }
        return ['serial_numbers' => $serials, 'order_id' => (int) $data['order_id']];
    }

    /** Validate sell request. @agent-use: POST /api/product-serials/sell */
    public function validateSell(array $input): array
    {
        $serials = $this->serialsFromInput($input);
        $data = $this->run($input, [
            'order_id' => 'required|integer|greater_than[0]',
        ]);
        if (empty($serials)) {
            throw new InvalidArgumentException('serial_numbers is required');
        }
        return ['serial_numbers' => $serials, 'order_id' => (int) $data['order_id']];
    }

    /** Validate return request. @agent-use: POST /api/product-serials/return */
    public function validateReturn(array $input): array
    {
        $serials = $this->serialsFromInput($input);
        $data = $this->run($input, [
            'order_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'reason' => 'permit_empty|string|max_length[255]',
        ]);
        if (empty($serials)) {
            throw new InvalidArgumentException('serial_numbers is required');
        }
        return [
            'serial_numbers' => $serials,
            'order_id' => isset($data['order_id']) ? (int) $data['order_id'] : null,
            'reason' => $data['reason'] ?? null,
        ];
    }

    private function serialsFromInput(array $input): array
    {
        $serials = [];
        if (! empty($input['serial_numbers']) && is_array($input['serial_numbers'])) {
            $serials = $input['serial_numbers'];
        } elseif (! empty($input['serial_number']) && is_string($input['serial_number'])) {
            $serials = [$input['serial_number']];
        }
        $serials = array_values(array_filter(array_map(static function ($value) {
            if (is_numeric($value)) {
                return (string) $value;
            }
            if (is_string($value)) {
                return trim($value);
            }
            return null;
        }, $serials), static fn ($v) => $v !== null && $v !== ''));

        return array_values(array_unique($serials));
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return $this->v->getValidated();
    }
}
