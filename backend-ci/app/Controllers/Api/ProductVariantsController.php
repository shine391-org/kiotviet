<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\ProductVariants\ProductVariantService;
use CodeIgniter\API\ResponseTrait;

/** Product variants API. @agent-controller: ProductVariants @agent-pattern: Thin controller - routing only */
class ProductVariantsController extends BaseController
{
    use ResponseTrait;

    protected ProductVariantService $service;
    public function __construct() { $this->service = service('productVariantService'); }

    /** Show variant detail. @agent-use: GET /api/variants/{id} @agent-pattern: Get by id */
    public function show($id) { return $this->wrap(fn () => $this->respond($this->service->show((int) $id))); }

    /** Create variant for product. @agent-use: POST /api/products/{productId}/variants @agent-pattern: Thin create */
    public function create($productId) { $data = $this->safeInput(); return $this->wrap(fn () => $this->respondCreated($this->service->create((int) $productId, $data))); }

    /** Update variant. @agent-use: PUT /api/variants/{id} @agent-pattern: Thin update */
    public function update($id) { $data = $this->safeInput(); return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $data))); }

    /** Delete variant (soft). @agent-use: DELETE /api/variants/{id} @agent-pattern: Thin delete */
    public function delete($id) { return $this->wrap(fn () => $this->respond($this->service->delete((int) $id))); }

    /** Upload multiple files. @agent-use: POST /api/variants/{id}/upload-multiple @agent-pattern: Thin upload batch */
    public function uploadMultiple($id) { $files = $this->request->getFiles(); return $this->wrap(fn () => $this->respond($this->service->uploadMultiple((int) $id, $files))); }

    /** Attach existing images. @agent-use: POST /api/variants/{id}/images/attach-multiple @agent-pattern: Delegate attach */
    public function attachImages($id) { $payload = $this->request->getJSON(true) ?? []; $imageIds = $payload['image_ids'] ?? $payload['imageIds'] ?? []; return $this->wrap(fn () => $this->respond($this->service->attachImages((int) $id, (array) $imageIds))); }

    /** Attribute values. @agent-use: GET /api/variants/{id}/attribute-values @agent-pattern: Delegate attributes */
    public function attributeValues($id) { return $this->wrap(fn () => $this->respond($this->service->attributeValues((int) $id))); }

    /** Sync attribute values. @agent-use: POST /api/variants/{id}/attribute-values/sync @agent-pattern: Delegate sync */
    public function syncAttributeValues($id) { $payload = $this->request->getJSON(true) ?? []; $values = $payload['attribute_values'] ?? $payload['attributeValues'] ?? []; return $this->wrap(fn () => $this->respond($this->service->syncAttributeValues((int) $id, (array) $values))); }

    /** Remove attribute from variant. @agent-use: DELETE /api/attributes/remove-from-variant/{variantId}/{attributeId} @agent-pattern: Delegate delete */
    public function removeAttributeFromVariant($variantId, $attributeId) { return $this->wrap(fn () => $this->respond($this->service->removeAttributeFromVariant((int) $variantId, (int) $attributeId))); }

    /** List deleted variants. @agent-use: GET /api/variants/deleted @agent-pattern: Delegate deleted listing */
    public function deletedList() { $productId = $this->request->getGet('product_id'); return $this->wrap(fn () => $this->respond($this->service->deletedList($productId ? (int) $productId : null))); }

    /** Restore variant. @agent-use: PUT /api/variants/{id}/restore @agent-pattern: Delegate restore */
    public function restore($id) { return $this->wrap(fn () => $this->respond($this->service->restore((int) $id))); }

    /** Hard delete variant. @agent-use: DELETE /api/variants/{id}/hard @agent-pattern: Delegate hard delete */
    public function hardDelete($id) { return $this->wrap(fn () => $this->respond($this->service->hardDelete((int) $id))); }

    /** Shared try/catch wrapper. @agent-use: Controller error handling @agent-pattern: Wrap service calls */
    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }

    /** Safely fetch body as JSON or form data to avoid parse errors. */
    private function safeInput(): array
    {
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) { return $json; }
        } catch (\Throwable $e) {}

        $raw = $this->request->getRawInput();
        return is_array($raw) ? $raw : [];
    }
}
