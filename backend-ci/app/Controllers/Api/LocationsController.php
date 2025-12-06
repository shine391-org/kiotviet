<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Locations\LocationService;
use CodeIgniter\API\ResponseTrait;

/**
 * Locations API - Vietnam administrative divisions (provinces, districts, wards)
 *
 * @agent-controller: Locations
 * @agent-pattern: Thin controller - routing only
 */
class LocationsController extends BaseController
{
    use ResponseTrait;

    protected LocationService $service;

    public function __construct()
    {
        $this->service = service('locationService');
    }

    /** List all provinces. @agent-use: GET /api/locations/provinces */
    public function provinces()
    {
        return $this->wrap(fn () => $this->respond($this->service->listProvinces($this->request->getGet())));
    }

    /** List districts by province. @agent-use: GET /api/locations/provinces/{id}/districts */
    public function districts($provinceId)
    {
        return $this->wrap(fn () => $this->respond($this->service->listDistricts((int) $provinceId, $this->request->getGet())));
    }

    /** List wards by district. @agent-use: GET /api/locations/districts/{id}/wards */
    public function wards($districtId)
    {
        return $this->wrap(fn () => $this->respond($this->service->listWards((int) $districtId, $this->request->getGet())));
    }

    /** Get location statistics. @agent-use: GET /api/locations/stats */
    public function stats()
    {
        return $this->wrap(fn () => $this->respond($this->service->getStats()));
    }

    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->failNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
