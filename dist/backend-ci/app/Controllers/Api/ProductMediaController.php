<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\ProductMedia\ProductMediaService;
use CodeIgniter\API\ResponseTrait;

/**
 * Media library endpoints.
 *
 * @agent-controller: Product media
 * @agent-pattern: Thin controller - routing only
 */
class ProductMediaController extends BaseController
{
    use ResponseTrait;

    protected ProductMediaService $service;

    public function __construct()
    {
        $this->service = service('productMediaService');
    }

    /** List media library with attachment flags. @agent-use: GET /api/products/media/library @agent-pattern: Delegate to service */
    public function library()
    {
        return $this->wrap(fn () => $this->respond($this->service->library($this->request->getGet())));
    }

    /** List media by year/month. @agent-use: GET /api/products/media/by-date @agent-pattern: Delegate to service */
    public function byDate()
    {
        return $this->wrap(fn () => $this->respond($this->service->byDate($this->request->getGet())));
    }

    /** Search media by SKU or product code. @agent-use: GET /api/products/media/search-sku @agent-pattern: Delegate to service */
    public function searchSku()
    {
        return $this->wrap(fn () => $this->respond($this->service->searchSku($this->request->getGet())));
    }

    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
