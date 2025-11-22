<?php

namespace App\Services\ProductVariants;

use App\Repositories\ProductVariants\ProductVariantRepository;
use App\Repositories\Products\ProductRepository;
use App\Validators\ProductVariantValidator;
use CodeIgniter\HTTP\Files\UploadedFile;
use InvalidArgumentException;
use RuntimeException;

/** Product variant business logic. @agent-service: Variant service layer @agent-pattern: Service orchestrator @agent-reusable: HIGH */
class ProductVariantService
{
    protected ProductVariantRepository $repo; protected ProductVariantValidator $validator; protected ProductRepository $productRepo;
    public function __construct(?ProductVariantRepository $repo = null, ?ProductVariantValidator $validator = null, ?ProductRepository $productRepo = null)
    { $this->repo = $repo ?? new ProductVariantRepository(); $this->validator = $validator ?? new ProductVariantValidator(); $this->productRepo = $productRepo ?? new ProductRepository(); }

    /** Get variant detail. @agent-use: GET /api/variants/{id} @agent-pattern: Get by id */
    public function show(int $id): array { return ['success' => true, 'data' => $this->requireVariant($id)]; }

    /** Create variant belonging to product. @agent-use: POST /api/products/{productId}/variants @agent-pattern: Standard create */
    public function create(int $productId, array $data): array
    { $this->requireProduct($productId); $payload = $this->validator->validateCreate(['product_id' => $productId] + $data); $variant = $this->repo->create($payload); return ['success' => true, 'data' => $variant]; }

    /** Update variant. @agent-use: PUT /api/variants/{id} @agent-pattern: Standard update */
    public function update(int $id, array $data): array
    { $this->requireVariant($id); $validated = $this->validator->validateUpdate($id, $data); $this->repo->update($id, $validated); return ['success' => true]; }

    /** Soft delete variant. @agent-use: DELETE /api/variants/{id} @agent-pattern: Soft delete */
    public function delete(int $id): array { $this->requireVariant($id); $this->repo->delete($id); return ['success' => true]; }

    /** Upload multiple files for a variant. @agent-use: POST /api/variants/{id}/upload-multiple @agent-pattern: Batch upload */
    public function uploadMultiple(int $variantId, array $files): array
    {
        $this->requireVariant($variantId); $uploads = $this->normalizeFiles($files); if (empty($uploads)) { throw new InvalidArgumentException('No files uploaded'); }
        $urls = $this->storeFiles($uploads);
        return ['success' => true, 'data' => ['variant_id' => $variantId, 'files' => $urls]];
    }

    /** Attach uploaded images to variant. @agent-use: POST /api/variants/{id}/images/attach-multiple @agent-pattern: Bulk attach */
    public function attachImages(int $variantId, array $imageIds): array
    { $this->requireVariant($variantId); $ids = $this->validator->validateImageIds($imageIds); $count = $this->repo->attachImages($variantId, $ids); return ['success' => true, 'attached_count' => $count, 'message' => 'Attached images to variant']; }

    /** Attribute values of variant. @agent-use: GET /api/variants/{id}/attribute-values @agent-pattern: Read attributes */
    public function attributeValues(int $variantId): array
    { $this->requireVariant($variantId); return ['success' => true, 'data' => $this->repo->attributeValues($variantId)]; }

    /** Sync attribute values for variant. @agent-use: POST /api/variants/{id}/attribute-values/sync @agent-pattern: Sync attributes */
    public function syncAttributeValues(int $variantId, array $values): array
    { $this->requireVariant($variantId); $normalized = $this->validator->validateAttributeValues($values); $rows = $this->repo->syncAttributeValues($variantId, $normalized); return ['success' => true, 'data' => $rows]; }

    /** Remove attribute assignment from variant. @agent-use: DELETE /api/attributes/remove-from-variant/{variantId}/{attributeId} @agent-pattern: Target delete */
    public function removeAttributeFromVariant(int $variantId, int $attributeId): array
    { $this->requireVariant($variantId); $this->repo->removeAttributeFromVariant($variantId, $attributeId); return ['success' => true, 'message' => 'Removed attribute from variant']; }

    /** List deleted variants. @agent-use: GET /api/variants/deleted @agent-pattern: Deleted list */
    public function deletedList(?int $productId = null): array
    { return ['success' => true, 'data' => ['variants' => $this->repo->deletedList($productId)]]; }

    /** Restore soft-deleted variant. @agent-use: PUT /api/variants/{id}/restore @agent-pattern: Restore */
    public function restore(int $id): array
    { if (! $this->repo->findById($id, true)) { throw new RuntimeException('Variant not found'); } $this->repo->restore($id); return ['success' => true]; }

    /** Hard delete variant. @agent-use: DELETE /api/variants/{id}/hard @agent-pattern: Hard delete */
    public function hardDelete(int $id): array
    { if (! $this->repo->findById($id, true)) { throw new RuntimeException('Variant not found'); } $this->repo->hardDelete($id); return ['success' => true]; }

    private function requireVariant(int $id): array
    { $variant = $this->repo->findById($id); if (! $variant) { throw new RuntimeException('Variant not found'); } return $variant; }

    private function requireProduct(int $productId): void
    { if (! $this->productRepo->findById($productId)) { throw new RuntimeException('Product not found'); } }

    /** @return list<UploadedFile> */
    private function normalizeFiles(array $files): array
    {
        $normalized = [];
        foreach ($files as $file) { if ($file instanceof UploadedFile) { $normalized[] = $file; } elseif (is_array($file)) { foreach ($file as $f) { if ($f instanceof UploadedFile) { $normalized[] = $f; } } } }
        return array_values(array_filter($normalized, static fn (UploadedFile $f) => $f->isValid()));
    }

    /** Move uploaded files into storage and return URLs. */
    private function storeFiles(array $files): array
    {
        $uploadPath = WRITEPATH . 'uploads/variants'; if (! is_dir($uploadPath)) { mkdir($uploadPath, 0775, true); }
        $urls = []; foreach ($files as $file) { $newName = $file->getRandomName(); $file->move($uploadPath, $newName); $urls[] = '/uploads/variants/' . $newName; }
        return $urls;
    }
}
