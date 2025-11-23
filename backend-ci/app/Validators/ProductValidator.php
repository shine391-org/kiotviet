<?php

namespace App\Validators;

use App\Repositories\ProductVariants\ProductVariantRepository;
use App\Repositories\Products\ProductRepository;
use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/** Product input validation. @agent-validator: Product input validation @agent-pattern: Validation first @agent-reusable: HIGH */
class ProductValidator
{
    protected Validation $v; protected ProductRepository $productRepo; protected ProductVariantRepository $variantRepo;

    public function __construct(?Validation $validation = null, ?ProductRepository $productRepo = null, ?ProductVariantRepository $variantRepo = null)
    {
        // Use non-shared instance to avoid cross-test/state bleed
        $this->v = $validation ?? Services::validation(null, false);
        $this->productRepo = $productRepo ?? new ProductRepository();
        $this->variantRepo = $variantRepo ?? new ProductVariantRepository();
    }

    /** Validate list filters. @agent-use: Listing endpoints @agent-pattern: Standard list validation */
    public function validateListFilters(array $input): array
    {
        $data = array_merge(['page' => 1, 'limit' => 20, 'include_variants' => false], $input);
        $rules = ['page' => 'permit_empty|integer|greater_than_equal_to[1]', 'limit' => 'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[200]', 'search' => 'permit_empty|string|max_length[255]', 'status' => 'permit_empty|string|max_length[50]', 'product_type' => 'permit_empty|string|max_length[50]', 'include_variants' => 'permit_empty'];
        // Cast boolean-ish flag before validate to tránh lỗi in_list khi mặc định false
        $data['include_variants'] = filter_var($data['include_variants'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $v = $this->run($data, $rules, []);
        $v['page'] = (int) ($v['page'] ?? 1); $v['limit'] = (int) ($v['limit'] ?? 20);
        $v['include_variants'] = (bool) ($v['include_variants'] ?? false);
        return $v;
    }

    /** Validate product creation payload. @agent-use: Create product request @agent-pattern: Standard create validation */
    public function validateCreate(array $input): array
    {
        $rules = ['code' => 'required|string|max_length[100]', 'name' => 'required|string|max_length[255]', 'product_type' => 'permit_empty|string|max_length[50]', 'barcode' => 'permit_empty|string|max_length[100]', 'status' => 'permit_empty|string|max_length[50]', 'has_variants' => 'permit_empty|in_list[0,1,true,false]', 'purchase_price' => 'permit_empty|numeric', 'selling_price' => 'permit_empty|numeric', 'wholesale_price' => 'permit_empty|numeric', 'stock_quantity' => 'permit_empty|numeric', 'alert_stock' => 'permit_empty|numeric'];
        $validated = $this->normalizeBooleans($this->run($input, $rules, $this->messages()), ['has_variants']);
        $this->assertCodeUnique($validated['code']);
        return $validated;
    }

    /** Validate product update payload. @agent-use: Update product request @agent-pattern: Standard update validation */
    public function validateUpdate(int $id, array $input): array
    {
        $rules = ['code' => 'permit_empty|string|max_length[100]', 'name' => 'permit_empty|string|max_length[255]', 'product_type' => 'permit_empty|string|max_length[50]', 'barcode' => 'permit_empty|string|max_length[100]', 'status' => 'permit_empty|string|max_length[50]', 'has_variants' => 'permit_empty|in_list[0,1,true,false]', 'purchase_price' => 'permit_empty|numeric', 'selling_price' => 'permit_empty|numeric', 'wholesale_price' => 'permit_empty|numeric', 'stock_quantity' => 'permit_empty|numeric', 'alert_stock' => 'permit_empty|numeric'];
        $validated = $this->normalizeBooleans($this->run($input, $rules, $this->messages()), ['has_variants']);
        if (empty($validated)) { throw new InvalidArgumentException('No fields to update'); }
        if (! empty($validated['code'])) { $this->assertCodeUnique($validated['code'], $id); }
        return $validated;
    }

    /** Validate list of image ids. @agent-use: Attach images request @agent-pattern: Array of ids validation */
    public function validateImageIds(array $imageIds): array
    {
        if (empty($imageIds)) { throw new InvalidArgumentException('image_ids required'); }
        return array_values(array_map(static fn ($id) => (int) $id, $imageIds));
    }

    private function run(array $data, array $rules, array $messages = []): array
    {
        if (! $this->v->setRules($rules, $messages)->run($data)) { throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data'); }
        return $this->v->getValidated();
    }

    private function normalizeBooleans(array $data, array $fields): array
    {
        foreach ($fields as $field) { if (array_key_exists($field, $data)) { $data[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE); } }
        return $data;
    }

    /** Custom VN validation messages. */
    private function messages(): array
    {
        return [
            'code' => [
                'required' => 'Mã sản phẩm không được để trống',
                'max_length' => 'Mã sản phẩm không được vượt quá 100 ký tự',
            ],
            'name' => [
                'required' => 'Tên sản phẩm không được để trống',
                'max_length' => 'Tên sản phẩm không được vượt quá 255 ký tự',
            ],
            'selling_price' => [
                'numeric' => 'Giá bán phải là số',
            ],
            'purchase_price' => [
                'numeric' => 'Giá vốn phải là số',
            ],
            'wholesale_price' => [
                'numeric' => 'Giá sỉ phải là số',
            ],
            'stock_quantity' => [
                'numeric' => 'Tồn kho phải là số',
            ],
        ];
    }

    /** Ensure code unique across products and variants. @agent-use: Cross-table code validation @agent-pattern: Prevent SKU/code collision */
    private function assertCodeUnique(string $code, ?int $excludeId = null): void
    {
        $trimmed = trim($code);
        if ($trimmed === '') { return; }

        if ($this->productRepo->codeExists($trimmed, $excludeId)) {
            throw new InvalidArgumentException('Mã sản phẩm đã tồn tại trong danh sách sản phẩm');
        }

        if ($this->variantRepo->skuExists($trimmed)) {
            throw new InvalidArgumentException('Mã sản phẩm đã tồn tại trong danh sách phiên bản');
        }
    }
}
