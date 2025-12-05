<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Manufacturing\BOMService;
use CodeIgniter\API\ResponseTrait;
use InvalidArgumentException;
use RuntimeException;

/**
 * BOM API controller.
 *
 * @agent-controller: BOM
 * @agent-pattern: Thin controller - routing only
 */
class BOMsController extends BaseController
{
    use ResponseTrait;

    protected BOMService $service;

    public function __construct()
    {
        $this->service = service('bomService');
    }

    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->show((int) $id)));
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($data)));
    }

    public function update($id)
    {
        $data = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $data)));
    }

    public function delete($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->delete((int) $id)));
    }

    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (RuntimeException $e) {
            return $this->failNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
