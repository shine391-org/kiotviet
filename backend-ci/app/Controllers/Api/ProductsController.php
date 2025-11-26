<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Products\ProductService;
use App\Services\Products\ProductImportService;
use App\Services\Products\ProductExportService;
use CodeIgniter\API\ResponseTrait;

/** Products API. @agent-controller: Products @agent-pattern: Thin controller - routing only */
class ProductsController extends BaseController
{
    use ResponseTrait;

    protected ProductService $service;
    protected ProductImportService $importService;
    protected ProductExportService $exportService;

    public function __construct()
    {
        $this->service = service('productService');
        $this->importService = service('productImportService');
        $this->exportService = service('productExportService');
    }

    /** List products. @agent-use: GET /api/products @agent-pattern: Standard list pattern */
    public function index() { return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet()))); }

    /** Show product. @agent-use: GET /api/products/{id} @agent-pattern: Thin get by id */
    public function show($id = null)
    {
        $priceListId = $this->request->getGet('price_list_id');
        $priceListId = $priceListId !== null ? (int) $priceListId : null;
        return $this->wrap(fn () => $this->respond($this->service->get((int) $id, true, true, $priceListId)));
    }

    /** Detail with variants. @agent-use: GET /api/products/{id}/detail-with-variants @agent-pattern: Delegate to service */
    public function detailWithVariants($id)
    {
        $priceListId = $this->request->getGet('price_list_id');
        $priceListId = $priceListId !== null ? (int) $priceListId : null;
        return $this->wrap(fn () => $this->respond($this->service->get((int) $id, true, false, $priceListId)));
    }

    /** List variants. @agent-use: GET /api/products/{id}/variants @agent-pattern: Delegate to service */
    public function variants($id) { return $this->wrap(fn () => $this->respond($this->service->variants((int) $id))); }

    /** Create product. @agent-use: POST /api/products @agent-pattern: Thin create */
    public function create()
    {
        $data = $this->safeInput();
        return $this->wrap(fn () => $this->respondCreated($this->service->create($data)));
    }

    /** Update product. @agent-use: PUT /api/products/{id} @agent-pattern: Thin update */
    public function update($id)
    {
        $data = $this->safeInput();
        return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $data)));
    }

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

    /** Import products from Excel. @agent-endpoint: POST /api/products/import @agent-pattern: File upload delegation */
    public function import()
    {
        $file = $this->request->getFile('file');
        if (! $file || $file->getError() !== UPLOAD_ERR_OK || $file->hasMoved()) {
            return $this->failValidationErrors('Invalid file upload');
        }
        $ext = strtolower((string) $file->getExtension());
        if (! in_array($ext, ['xlsx', 'xls'], true)) {
            return $this->failValidationErrors('Only .xlsx or .xls files are allowed');
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->failValidationErrors('File too large (max 5MB)');
        }

        $tempDir = WRITEPATH . 'uploads';
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }
        $tempName = $file->getRandomName();
        $path = $tempDir . DIRECTORY_SEPARATOR . $tempName;

        if ($file->isValid()) {
            $file->move($tempDir, $tempName, true);
        } elseif (is_file($file->getTempName())) {
            // Testing/CLI fallback where is_uploaded_file() fails
            copy($file->getTempName(), $path);
        } else {
            return $this->failValidationErrors('Invalid file upload');
        }

        try {
            $result = $this->importService->importFromExcel($path);
            @unlink($path);
            return $this->respond($result);
        } catch (\Throwable $e) {
            @unlink($path);
            return $this->failServerError($e->getMessage());
        }
    }

    /** Export products to Excel. @agent-endpoint: GET /api/products/export @agent-pattern: Excel download response */
    public function export()
    {
        try {
            $filters = $this->request->getGet();
            $path = $this->exportService->exportToExcel($filters);
            $content = file_get_contents($path);
            @unlink($path);

            return $this->response
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="products_' . date('Ymd_His') . '.xlsx"')
                ->setBody($content);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /** Download import template. @agent-endpoint: GET /api/products/import/template @agent-pattern: Template download */
    public function importTemplate()
    {
        try {
            $path = $this->importService->generateTemplate();
            $content = file_get_contents($path);
            @unlink($path);

            return $this->response
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="products_import_template.xlsx"')
                ->setBody($content);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

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

    /** Safely fetch request body supporting JSON or form-data, without throwing parse errors. */
    private function safeInput(): array
    {
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) { return $json; }
        } catch (\Throwable $e) {
            // swallow and fallback below
        }

        $raw = $this->request->getRawInput();
        return is_array($raw) ? $raw : [];
    }
}
