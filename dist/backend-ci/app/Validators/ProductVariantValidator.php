<?php

namespace App\Validators;

use App\Repositories\ProductVariants\ProductVariantRepository;
use App\Repositories\Products\ProductRepository;
use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/** Product variant input validation @agent-validator: Variant input validation @agent-pattern: Validation first @agent-reusable: HIGH */
class ProductVariantValidator
{
    protected Validation $v; protected ProductVariantRepository $variantRepo; protected ProductRepository $productRepo;

    public function __construct(?Validation $validation = null, ?ProductVariantRepository $variantRepo = null, ?ProductRepository $productRepo = null)
    {
        // Use a non-shared validator instance to prevent cross-test/state bleed
        // from other validators that might mutate the shared Validation service.
        $this->v = $validation ?? Services::validation(null, false);
        $this->variantRepo = $variantRepo ?? new ProductVariantRepository();
        $this->productRepo = $productRepo ?? new ProductRepository();
    }

    /** Validate payload for creating a variant. @agent-use: Create variant request @agent-pattern: Standard create validation */
    public function validateCreate(array $input): array
    {
        $rules = ['product_id' => 'required|integer|greater_than[0]', 'variant_name' => 'permit_empty|string|max_length[255]', 'variant_signature' => 'permit_empty|string|max_length[255]', 'sku' => 'permit_empty|string|max_length[100]', 'barcode' => 'permit_empty|string|max_length[100]', 'price' => 'permit_empty|numeric', 'cost_price' => 'permit_empty|numeric', 'stock_quantity' => 'permit_empty|numeric', 'min_stock' => 'permit_empty|numeric', 'max_stock' => 'permit_empty|numeric', 'image_url' => 'permit_empty|string|max_length[255]', 'attributes' => 'permit_empty|string', 'status' => 'permit_empty|string|max_length[50]'];
        $validated = $this->run($input, $rules, $this->messages());
        if (! empty($validated['sku'])) { $this->assertSkuUnique($validated['sku']); }
        return $validated;
    }

    /** Validate payload for updating a variant. @agent-use: Update variant request @agent-pattern: Standard update validation */
    public function validateUpdate(int $id, array $input): array
    {
        $rules = ['variant_name' => 'permit_empty|string|max_length[255]', 'variant_signature' => 'permit_empty|string|max_length[255]', 'sku' => 'permit_empty|string|max_length[100]', 'barcode' => 'permit_empty|string|max_length[100]', 'price' => 'permit_empty|numeric', 'cost_price' => 'permit_empty|numeric', 'stock_quantity' => 'permit_empty|numeric', 'min_stock' => 'permit_empty|numeric', 'max_stock' => 'permit_empty|numeric', 'image_url' => 'permit_empty|string|max_length[255]', 'attributes' => 'permit_empty|string', 'status' => 'permit_empty|string|max_length[50]'];
        $validated = $this->run($input, $rules, $this->messages()); if (empty($validated)) { throw new InvalidArgumentException('No fields to update'); }
        if (! empty($validated['sku'])) { $this->assertSkuUnique($validated['sku'], $id); }
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

    private function run(array $data, array $rules, array $messages = []): array
    {
        if (! $this->v->setRules($rules, $messages)->run($data)) { throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data'); }
        return $this->v->getValidated();
    }

    /** Ensure SKU unique across variants and products. @agent-use: Cross-table SKU validation @agent-pattern: Prevent product/variant collision */
    private function assertSkuUnique(string $sku, ?int $excludeId = null): void
    {
        $trimmed = trim($sku);
        if ($trimmed === '') { return; }

        if ($this->variantRepo->skuExists($trimmed, $excludeId)) {
            throw new InvalidArgumentException('SKU đã tồn tại trong danh sách phiên bản');
        }

        if ($this->productRepo->codeExists($trimmed)) {
            throw new InvalidArgumentException('SKU đã tồn tại trong danh sách sản phẩm');
        }
    }

    /** Custom VN validation messages. */
    private function messages(): array
    {
        return [
            'product_id' => [
                'required' => 'Thiếu product_id',
                'integer' => 'product_id phải là số',
                'greater_than' => 'product_id phải lớn hơn 0',
            ],
            'sku' => [
                'string' => 'SKU phải là chuỗi',
                'max_length' => 'SKU không được vượt quá 100 ký tự',
            ],
            'price' => [
                'numeric' => 'Giá bán phải là số',
            ],
            'cost_price' => [
                'numeric' => 'Giá vốn phải là số',
            ],
            'stock_quantity' => [
                'numeric' => 'Tồn kho phải là số',
            ],
        ];
    }
}
