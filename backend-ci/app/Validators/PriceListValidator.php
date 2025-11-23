<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/** Price list input validation. @agent-validator: Price list @agent-pattern: Validation first @agent-reusable: HIGH */
class PriceListValidator
{
    protected Validation $v;
    public function __construct(?Validation $v = null) { $this->v = $v ?? Services::validation(null, false); }

    /** Validate listing filters. */
    public function validateListFilters(array $input): array
    {
        $data = array_merge(['page' => 1, 'limit' => 20, 'status' => null, 'is_active' => null], $input);
        $rules = [
            'page' => 'permit_empty|integer|greater_than_equal_to[1]',
            'limit' => 'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[200]',
            'search' => 'permit_empty|string|max_length[255]',
            'type' => 'permit_empty|in_list[base,wholesale,retail,vip,custom]',
            'status' => 'permit_empty|in_list[active,expired,upcoming,inactive]',
            'apply_to_group_id' => 'permit_empty|integer|greater_than_equal_to[1]',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
        ];
        $v = $this->run($data, $rules);
        $v['page'] = (int) ($v['page'] ?? 1); $v['limit'] = (int) ($v['limit'] ?? 20);
        $v['apply_to_group_id'] = isset($v['apply_to_group_id']) ? (int) $v['apply_to_group_id'] : null;
        $v['is_active'] = array_key_exists('is_active', $v) ? (bool) filter_var($v['is_active'], FILTER_VALIDATE_BOOLEAN) : null;
        return $v;
    }

    /** Validate create payload. */
    public function validateCreate(array $input): array
    {
        $rules = [
            'name' => 'required|string|max_length[255]',
            'type' => 'permit_empty|in_list[base,wholesale,retail,vip,custom]',
            'description' => 'permit_empty|string',
            'apply_to_groups' => 'permit_empty',
            'start_date' => 'permit_empty|valid_date',
            'end_date' => 'permit_empty|valid_date',
            'priority' => 'permit_empty|integer',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
        ];
        $v = $this->run($input, $rules);
        return $this->postProcess($v, true);
    }

    /** Validate update payload. */
    public function validateUpdate(array $input): array
    {
        $rules = [
            'name' => 'permit_empty|string|max_length[255]',
            'type' => 'permit_empty|in_list[base,wholesale,retail,vip,custom]',
            'description' => 'permit_empty|string',
            'apply_to_groups' => 'permit_empty',
            'start_date' => 'permit_empty|valid_date',
            'end_date' => 'permit_empty|valid_date',
            'priority' => 'permit_empty|integer',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
        ];
        $v = $this->run($input, $rules);
        if (empty($v)) { throw new InvalidArgumentException('No fields to update'); }
        return $this->postProcess($v, false);
    }

    /** Validate price list items payload. */
    public function validateItems(array $items): array
    {
        if (empty($items)) { throw new InvalidArgumentException('items is required'); }
        $result = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            if ($productId <= 0) { throw new InvalidArgumentException('product_id is required'); }
            $variantId = $item['variant_id'] ?? null;
            $price = (float) ($item['price'] ?? 0);
            $discountPercent = (float) ($item['discount_percent'] ?? 0);
            $discountAmount = (float) ($item['discount_amount'] ?? 0);
            if ($price < 0 || $discountPercent < 0 || $discountAmount < 0) {
                throw new InvalidArgumentException('price and discounts must be non-negative');
            }
            if ($discountPercent > 100) { throw new InvalidArgumentException('discount_percent must be <= 100'); }
            $result[] = [
                'product_id' => $productId,
                'variant_id' => $variantId !== null ? (int) $variantId : null,
                'price' => $price,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
            ];
        }
        return $result;
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return $this->v->getValidated();
    }

    private function postProcess(array $data, bool $isCreate): array
    {
        if (isset($data['apply_to_groups'])) {
            $data['apply_to_groups'] = array_values(array_filter(array_map('intval', (array) $data['apply_to_groups'])));
            // Optional existence check if customer_groups table exists
            $db = \Config\Database::connect('tests');
            if ($db->tableExists('customer_groups') && ! empty($data['apply_to_groups'])) {
                $count = $db->table('customer_groups')->whereIn('id', $data['apply_to_groups'])->countAllResults();
                if ($count !== count($data['apply_to_groups'])) {
                    throw new InvalidArgumentException('apply_to_groups contains invalid group id');
                }
            }
        }
        if (isset($data['priority'])) { $data['priority'] = (int) $data['priority']; }
        if (isset($data['is_active'])) { $data['is_active'] = (bool) filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN); }
        $start = $data['start_date'] ?? null; $end = $data['end_date'] ?? null;
        if ($start && $end && $start > $end) { throw new InvalidArgumentException('start_date must be before end_date'); }
        if ($isCreate && empty($data['priority'])) { $data['priority'] = 0; }
        return $data;
    }
}
