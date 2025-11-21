<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/** Product variant input validation @agent-validator: Variant input validation @agent-pattern: Validation first @agent-reusable: HIGH */
class ProductVariantValidator
{
    protected Validation $v;
    public function __construct(?Validation $validation = null) { $this->v = $validation ?? Services::validation(); }

    /** Validate payload for creating a variant. @agent-use: Create variant request @agent-pattern: Standard create validation */
    public function validateCreate(array $input): array
    {
        $rules = ['product_id' => 'required|integer|greater_than[0]', 'variant_name' => 'permit_empty|string|max_length[255]', 'variant_signature' => 'permit_empty|string|max_length[255]', 'sku' => 'permit_empty|string|max_length[100]', 'barcode' => 'permit_empty|string|max_length[100]', 'price' => 'permit_empty|numeric', 'cost_price' => 'permit_empty|numeric', 'stock_quantity' => 'permit_empty|numeric', 'min_stock' => 'permit_empty|numeric', 'max_stock' => 'permit_empty|numeric', 'image_url' => 'permit_empty|string|max_length[255]', 'attributes' => 'permit_empty|string', 'status' => 'permit_empty|string|max_length[50]'];
        return $this->run($input, $rules);
    }

    /** Validate payload for updating a variant. @agent-use: Update variant request @agent-pattern: Standard update validation */
    public function validateUpdate(array $input): array
    {
        $rules = ['variant_name' => 'permit_empty|string|max_length[255]', 'variant_signature' => 'permit_empty|string|max_length[255]', 'sku' => 'permit_empty|string|max_length[100]', 'barcode' => 'permit_empty|string|max_length[100]', 'price' => 'permit_empty|numeric', 'cost_price' => 'permit_empty|numeric', 'stock_quantity' => 'permit_empty|numeric', 'min_stock' => 'permit_empty|numeric', 'max_stock' => 'permit_empty|numeric', 'image_url' => 'permit_empty|string|max_length[255]', 'attributes' => 'permit_empty|string', 'status' => 'permit_empty|string|max_length[50]'];
        $validated = $this->run($input, $rules); if (empty($validated)) { throw new InvalidArgumentException('No fields to update'); }
        return $validated;
    }

    /** Validate list of image ids. @agent-use: Attach images request @agent-pattern: Array of ids validation */
    public function validateImageIds(array $imageIds): array
    {
        if (empty($imageIds)) { throw new InvalidArgumentException('image_ids required'); }
        return array_values(array_map(static fn ($id) => (int) $id, $imageIds));
    }

    /** Validate attribute values payload. @agent-use: Sync attribute values @agent-pattern: Sanitize attribute values */
    public function validateAttributeValues(array $values): array
    {
        if (! is_array($values)) { throw new InvalidArgumentException('attribute_values must be array'); }
        $normalized = [];
        foreach ($values as $val) { if (! is_array($val) || empty($val['attribute_id'])) { continue; } $normalized[] = ['product_id' => isset($val['product_id']) ? (int) $val['product_id'] : null, 'attribute_id' => (int) $val['attribute_id'], 'option_id' => isset($val['option_id']) ? (int) $val['option_id'] : null, 'value_text' => $val['value_text'] ?? null]; }
        return $normalized;
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) { throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data'); }
        return $this->v->getValidated();
    }
}
