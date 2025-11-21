<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Products\ProductService;
use CodeIgniter\API\ResponseTrait;

/** Products API. @agent-controller: Products @agent-pattern: Thin controller - routing only */
class ProductsController extends BaseController
{
    use ResponseTrait;

    protected ProductService $service;
    public function __construct() { $this->service = service('productService'); }

    /** List products. @agent-use: GET /api/products @agent-pattern: Standard list pattern */
    public function index() { return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet()))); }

    /** Show product. @agent-use: GET /api/products/{id} @agent-pattern: Thin get by id */
    public function show($id = null) { return $this->wrap(fn () => $this->respond($this->service->get((int) $id))); }

    /** Detail with variants. @agent-use: GET /api/products/{id}/detail-with-variants @agent-pattern: Delegate to service */
    public function detailWithVariants($id) { return $this->wrap(fn () => $this->respond($this->service->get((int) $id, true, false))); }

    /** List variants. @agent-use: GET /api/products/{id}/variants @agent-pattern: Delegate to service */
    public function variants($id) { return $this->wrap(fn () => $this->respond($this->service->variants((int) $id))); }

    /** Create product. @agent-use: POST /api/products @agent-pattern: Thin create */
    public function create() { $data = $this->request->getJSON(true) ?? []; return $this->wrap(fn () => $this->respondCreated($this->service->create($data))); }

    /** Update product. @agent-use: PUT /api/products/{id} @agent-pattern: Thin update */
    public function update($id) { $data = $this->request->getJSON(true) ?? []; return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $data))); }

    /** Delete product. @agent-use: DELETE /api/products/{id} @agent-pattern: Thin delete */
    public function delete($id) { return $this->wrap(fn () => $this->respond($this->service->delete((int) $id))); }

    /** Check product code. @agent-use: POST /api/products/check-code @agent-pattern: Delegate exists check */
    public function checkCode() { $code = $this->request->getPost('code'); $exclude = $this->request->getPost('exclude_id'); return $this->wrap(fn () => $this->respond($this->service->checkCode((string) $code, $exclude ? (int) $exclude : null))); }

    /** Images list. @agent-use: GET /api/products/{id}/images @agent-pattern: Media listing */
    public function images($id) { return $this->wrap(fn () => $this->respond($this->service->images((int) $id))); }

    /** Upload multiple images. @agent-use: POST /api/products/upload-multiple @agent-pattern: Thin upload batch */
    public function uploadMultiple() { $pid = (int) ($this->request->getPost('product_id') ?? 0); $bag = $this->request->getFiles(); $files = $bag['files'] ?? []; return $this->wrap(fn () => $this->respond($this->service->uploadMultiple($pid, $files))); }

    /** Upload single image. @agent-use: POST /api/products/upload @agent-pattern: Thin upload single */
    public function uploadSingle() { $pid = (int) ($this->request->getPost('product_id') ?? 0); $file = $this->request->getFile('file'); return $this->wrap(fn () => $this->respond($this->service->uploadSingle($pid, $file))); }

    /** Attach existing images. @agent-use: POST /api/products/{id}/images/attach-multiple @agent-pattern: Delegate attach */
    public function attachImages($id) { $payload = $this->request->getJSON(true) ?? []; $imageIds = $payload['image_ids'] ?? $payload['imageIds'] ?? []; return $this->wrap(fn () => $this->respond($this->service->attachImages((int) $id, (array) $imageIds))); }

    /** Set primary image. @agent-use: PUT /api/products/images/{id}/set-primary @agent-pattern: Delegate primary toggle */
    public function setPrimaryImage($imageId) { return $this->wrap(fn () => $this->respond($this->service->setPrimaryImage((int) $imageId))); }

    /** Delete image. @agent-use: DELETE /api/products/images/{id} @agent-pattern: Soft delete wrapper */
    public function deleteImage($imageId) { $hard = (bool) $this->request->getGet('hard'); return $this->wrap(fn () => $this->respond($this->service->deleteImage((int) $imageId, $hard))); }

    /** Used attribute options. @agent-use: GET /api/products/{id}/used-attribute-options @agent-pattern: Delegate attributes */
    public function usedAttributeOptions($id) { return $this->wrap(fn () => $this->respond($this->service->usedAttributeOptions((int) $id))); }

    /** Attribute values. @agent-use: GET /api/products/{id}/attribute-values @agent-pattern: Delegate attributes */
    public function productAttributeValues($id) { return $this->wrap(fn () => $this->respond($this->service->productAttributeValues((int) $id))); }

    /** Update attribute values. @agent-use: POST /api/products/{id}/attribute-values @agent-pattern: Delegate sync */
    public function updateProductAttributeValues($id) { $payload = $this->request->getJSON(true) ?? []; $values = $payload['attribute_values'] ?? []; return $this->wrap(fn () => $this->respond($this->service->updateProductAttributeValues((int) $id, $values))); }

    /** Remove attribute from product. @agent-use: DELETE /api/products/{pid}/attribute-values/{aid} @agent-pattern: Delegate delete */
    public function removeAttributeFromProduct($productId, $attributeId) { return $this->wrap(fn () => $this->respond($this->service->removeAttributeFromProduct((int) $productId, (int) $attributeId))); }

    /** Import stub. @agent-use: POST /api/products/import @agent-pattern: File ingest stub */
    public function import() { $file = $this->request->getFile('file'); return $this->wrap(fn () => $this->respond($this->service->import($file))); }

    /** Export CSV. @agent-use: GET /api/products/export @agent-pattern: Simple export */
    public function export() { return $this->wrap(fn () => $this->response->setHeader('Content-Type', 'text/csv')->setBody($this->service->export())); }

    /** Analytics summary. @agent-use: GET /api/products/{id}/analytics @agent-pattern: Delegate analytics */
    public function analytics($id) { return $this->wrap(fn () => $this->respond($this->service->analytics((int) $id))); }

    /** Shared try/catch wrapper. @agent-use: Controller error handling @agent-pattern: Wrap service calls */
    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
