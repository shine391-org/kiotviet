<?php

namespace App\Services\Products;

use App\Repositories\Products\ProductRepository;
use App\Services\PriceLists\PriceCalculatorService;
use App\Validators\ProductValidator;
use CodeIgniter\HTTP\Files\UploadedFile;
use InvalidArgumentException;
use RuntimeException;

/** Product business logic. @agent-service: Product service layer @agent-pattern: Service orchestrator @agent-reusable: HIGH */
class ProductService
{
    protected ProductRepository $repo; protected ProductValidator $validator; protected PriceCalculatorService $pricing;

    public function __construct(?ProductRepository $repo = null, ?ProductValidator $validator = null, ?PriceCalculatorService $pricing = null)
    {
        $this->repo = $repo ?? new ProductRepository();
        $this->validator = $validator ?? new ProductValidator();
        $this->pricing = $pricing ?? service('priceCalculatorService');
    }

    /** List products with filters, categories, variants. @agent-use: GET /api/products @agent-pattern: Standard list pattern */
    public function list(array $filters): array
    {
        $validated = $this->validator->validateListFilters($filters);
        $priceListId = isset($validated['price_list_id']) ? (int) $validated['price_list_id'] : null;

        $products = $this->repo->findAll($validated); $total = $this->repo->count($validated);
        $ids = array_column($products, 'id');
        if ($ids) {
            $categories = $this->repo->categoryMap($ids); $variants = $validated['include_variants'] ? $this->repo->variantMap($ids) : [];
            foreach ($products as &$row) {
                $row['category_ids'] = $categories[$row['id']] ?? [];
                if ($validated['include_variants']) {
                    $row['variants'] = $variants[$row['id']] ?? [];
                    $row['variants_v2'] = $row['variants'];
                }
                if ($priceListId) {
                    $row = $this->applyPriceList($row, $priceListId, $validated['include_variants']);
                }
            }
        }
        return ['success' => true, 'data' => $products, 'pagination' => $this->formatPagination($validated, $total)];
    }

    /** Fetch a single product (with variants/categories). @agent-use: GET /api/products/{id} @agent-pattern: Get by id */
    public function get(int $id, bool $withVariants = true, bool $withCategories = true, ?int $priceListId = null): array
    {
        $product = $this->requireProduct($id); $ids = [$product['id']];
        if ($withCategories) { $map = $this->repo->categoryMap($ids); $product['category_ids'] = $map[$id] ?? []; }
        if ($withVariants) { $variants = $this->repo->variantMap($ids); $product['variants'] = $variants[$id] ?? []; $product['variants_v2'] = $product['variants']; }

        if ($priceListId) {
            $product = $this->applyPriceList($product, $priceListId, $withVariants);
        }

        return ['success' => true, 'data' => $product];
    }

    /** Fetch variants only. @agent-use: GET /api/products/{id}/variants @agent-pattern: Variant list */
    public function variants(int $productId): array
    {
        $this->requireProduct($productId); return ['success' => true, 'data' => $this->repo->variantsByProduct($productId)];
    }

    /** Create product. @agent-use: POST /api/products @agent-pattern: Standard create */
    public function create(array $data): array
    {
        $validated = $this->validator->validateCreate($data);
        $product = $this->repo->create($validated);
        return ['success' => true, 'data' => $product];
    }

    /** Update product. @agent-use: PUT /api/products/{id} @agent-pattern: Standard update */
    public function update(int $id, array $data): array
    {
        $this->requireProduct($id); $validated = $this->validator->validateUpdate($id, $data);
        $this->repo->update($id, $validated);
        return ['success' => true];
    }

    /** Delete product (soft). @agent-use: DELETE /api/products/{id} @agent-pattern: Soft delete */
    public function delete(int $id): array
    {
        $this->requireProduct($id); return ['success' => $this->repo->delete($id)];
    }

    /** Check code uniqueness. @agent-use: POST /api/products/check-code @agent-pattern: Exists check */
    public function checkCode(string $code, ?int $excludeId = null): array
    {
        $trimmed = trim($code); if ($trimmed === '') { throw new InvalidArgumentException('code is required'); }

        $existsInProducts = $this->repo->codeExists($trimmed, $excludeId);
        $existsInVariants = $this->repo->codeExistsInVariants($trimmed);

        return [
            'success' => true,
            'exists' => $existsInProducts || $existsInVariants,
            'exists_in_products' => $existsInProducts,
            'exists_in_variants' => $existsInVariants,
            'message' => $existsInProducts
                ? 'Mã sản phẩm đã tồn tại trong danh sách sản phẩm'
                : ($existsInVariants ? 'Mã sản phẩm đã tồn tại trong danh sách phiên bản' : 'Mã có thể sử dụng'),
        ];
    }

    /** List product images. @agent-use: GET /api/products/{id}/images @agent-pattern: Media listing */
    public function images(int $productId): array
    {
        $this->requireProduct($productId); return ['success' => true, 'data' => $this->repo->images($productId)];
    }

    /** Upload multiple images. @agent-use: POST /api/products/upload-multiple @agent-pattern: Batch upload */
    public function uploadMultiple(int $productId, array $files): array
    {
        $this->requireProduct($productId); $uploads = $this->normalizeFiles($files); if (empty($uploads)) { throw new InvalidArgumentException('No files uploaded'); }
        $rows = $this->repo->insertImages($this->storeFiles($productId, $uploads));
        return ['success' => true, 'uploaded_count' => count($rows), 'data' => $rows];
    }

    /** Upload single image. @agent-use: POST /api/products/upload @agent-pattern: Single upload */
    public function uploadSingle(int $productId, ?UploadedFile $file): array
    {
        $this->requireProduct($productId); if (! $file || ! $file->isValid()) { throw new InvalidArgumentException('file is required'); }
        $rows = $this->repo->insertImages($this->storeFiles($productId, [$file]));
        return ['success' => true, 'data' => $rows[0] ?? []];
    }

    /** Attach uploaded images to product. @agent-use: POST /api/products/{id}/images/attach-multiple @agent-pattern: Bulk attach */
    public function attachImages(int $productId, array $imageIds): array
    {
        $this->requireProduct($productId);
        $ids = $this->validator->validateImageIds($imageIds);
        $result = $this->repo->attachImages($productId, $ids);

        $attachedCount = count($result['attached_ids']);
        $skippedCount = count($result['skipped_ids']);
        $missingCount = count($result['missing_ids']);
        $message = sprintf('Đã thêm %d ảnh, %d ảnh bị bỏ qua (đã tồn tại)', $attachedCount, $skippedCount);
        if ($missingCount > 0) { $message .= sprintf(', %d ảnh không tìm thấy', $missingCount); }

        return [
            'success' => true,
            'attached_count' => $attachedCount,
            'skipped_count' => $skippedCount,
            'missing_count' => $missingCount,
            'attached_ids' => $result['attached_ids'],
            'skipped_ids' => $result['skipped_ids'],
            'missing_ids' => $result['missing_ids'],
            'message' => $message,
        ];
    }

    /** Set primary image. @agent-use: PUT /api/products/images/{id}/set-primary @agent-pattern: Primary toggle */
    public function setPrimaryImage(int $imageId): array
    {
        $image = $this->repo->setPrimaryImage($imageId); if (! $image) { throw new RuntimeException('Image not found'); }
        return ['success' => true];
    }

    /** Delete product image. @agent-use: DELETE /api/products/images/{id} @agent-pattern: Soft delete */
    public function deleteImage(int $imageId, bool $hard = false): array
    {
        $this->repo->deleteImage($imageId, $hard); return ['success' => true];
    }

    /** Used attribute options. @agent-use: GET /api/products/{id}/used-attribute-options @agent-pattern: Read attributes */
    public function usedAttributeOptions(int $productId): array
    {
        $this->requireProduct($productId); return ['success' => true, 'data' => $this->repo->usedAttributeOptions($productId)];
    }

    /** Attribute values of product. @agent-use: GET /api/products/{id}/attribute-values @agent-pattern: Read attributes */
    public function productAttributeValues(int $productId): array
    {
        $this->requireProduct($productId); return ['success' => true, 'data' => $this->repo->productAttributeValues($productId)];
    }

    /** Update attribute values (product-level). @agent-use: POST /api/products/{id}/attribute-values @agent-pattern: Sync attributes */
    public function updateProductAttributeValues(int $productId, array $values): array
    {
        $this->requireProduct($productId); if (! is_array($values)) { throw new InvalidArgumentException('attribute_values must be array'); }
        $rows = $this->repo->syncProductAttributeValues($productId, $values); return ['success' => true, 'data' => $rows];
    }

    /** Remove attribute from product. @agent-use: DELETE /api/products/{id}/attribute-values/{attributeId} @agent-pattern: Target delete */
    public function removeAttributeFromProduct(int $productId, int $attributeId): array
    {
        $this->requireProduct($productId); $this->repo->removeAttributeFromProduct($productId, $attributeId); return ['success' => true, 'message' => 'Removed attribute from product'];
    }

    /** Import stub: store file only. @agent-use: POST /api/products/import @agent-pattern: File ingest stub */
    public function import(?UploadedFile $file): array
    {
        if (! $file || ! $file->isValid()) { throw new InvalidArgumentException('file is required'); }
        $path = WRITEPATH . 'imports'; if (! is_dir($path)) { mkdir($path, 0775, true); } $file->move($path, $file->getRandomName());
        return ['success' => true, 'data' => ['imported' => 0, 'failed' => 0, 'errors' => []]];
    }

    /** Export simple CSV string. @agent-use: GET /api/products/export @agent-pattern: Simple export */
    public function export(): string
    {
        $rows = $this->repo->findAll(['page' => 1, 'limit' => 500]); $csv = "id,code,name,price\n"; foreach ($rows as $r) { $csv .= sprintf("%s,%s,%s,%s\n", $r['id'], $r['code'], $r['name'], $r['selling_price'] ?? ''); } return $csv;
    }

    /** Analytics summary. @agent-use: GET /api/products/{id}/analytics @agent-pattern: Simple analytics */
    public function analytics(int $productId): array
    {
        $this->requireProduct($productId); $totalStock = $this->repo->totalStock($productId); $variantCount = count($this->repo->variantsByProduct($productId));
        return ['success' => true, 'data' => ['total_stock' => $totalStock, 'total_variant' => $variantCount]];
    }

    private function requireProduct(int $id): array
    {
        $product = $this->repo->findById($id); if (! $product) { throw new RuntimeException('Product not found'); } return $product;
    }

    /** Find product by code (null if not found). @agent-use: Import flows @agent-pattern: Lookup by unique code */
    public function findByCode(string $code): ?array
    {
        $trimmed = trim($code);
        if ($trimmed === '') {
            return null;
        }
        return $this->repo->findByCode($trimmed);
    }

    private function formatPagination(array $filters, int $total): array
    {
        $limit = $filters['limit'] ?? 20; $page = $filters['page'] ?? 1; $totalPages = (int) ceil($total / ($limit ?: 1));
        return ['page' => $page, 'limit' => $limit, 'total' => $total, 'total_pages' => $totalPages];
    }

    /** @return list<UploadedFile> */
    private function normalizeFiles(array $files): array
    {
        $normalized = []; foreach ($files as $file) { if ($file instanceof UploadedFile) { $normalized[] = $file; } elseif (is_array($file)) { foreach ($file as $f) { if ($f instanceof UploadedFile) { $normalized[] = $f; } } } }
        return array_values(array_filter($normalized, fn (UploadedFile $f) => $f->isValid()));
    }

    /** Prepare DB rows from uploaded files. */
    private function storeFiles(int $productId, array $files): array
    {
        $uploadPath = WRITEPATH . 'uploads/products'; if (! is_dir($uploadPath)) { mkdir($uploadPath, 0775, true); }
        $rows = [];
        foreach ($files as $file) { $newName = $file->getRandomName(); $file->move($uploadPath, $newName); $rows[] = ['product_id' => $productId, 'variant_id' => null, 'image_path' => '/uploads/products/' . $newName, 'image_url' => '/uploads/products/' . $newName, 'is_primary' => 0, 'sort_order' => 0, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), 'file_name' => $file->getClientName()]; }
        return $rows;
    }

    /** Apply price list to product (and variants if present). */
    private function applyPriceList(array $product, int $priceListId, bool $withVariants): array
    {
        $pricing = $this->pricing->getProductPriceByListId($priceListId, (int) $product['id']);
        $product['base_price'] = $pricing['base_price'];
        $product['price_after_discount'] = $pricing['final_price'];
        $product['applied_price_list_id'] = $pricing['applied_price_list_id'];
        $product['applied_price_list_name'] = $pricing['applied_price_list_name'];
        $product['price_list_type'] = $pricing['price_list_type'];

        if ($withVariants && ! empty($product['variants'])) {
            foreach ($product['variants'] as &$variant) {
                $p = $this->pricing->getProductPriceByListId($priceListId, (int) $product['id'], (int) $variant['id']);
                $variant['base_price'] = $p['base_price'];
                $variant['price_after_discount'] = $p['final_price'];
                $variant['applied_price_list_id'] = $p['applied_price_list_id'];
                $variant['applied_price_list_name'] = $p['applied_price_list_name'];
                $variant['price_list_type'] = $p['price_list_type'];
            }
            $product['variants_v2'] = $product['variants'];
        }

        return $product;
    }
}
