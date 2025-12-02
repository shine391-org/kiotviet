<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\POS\POSOfflineService;
use CodeIgniter\API\ResponseTrait;

/**
 * @agent-controller: POS offline queue
 * @agent-pattern: Thin controller - routing only
 */
class POSOfflineController extends BaseController
{
    use ResponseTrait;

    protected POSOfflineService $service;

    public function __construct()
    {
        $this->service = service('posOfflineService');
    }

    /** Enqueue offline items. @agent-use: POST /api/pos/offline/queue */
    public function queue()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->enqueue($payload)));
    }

    /** Sync offline batch. @agent-use: POST /api/pos/offline/sync */
    public function sync()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->sync($payload)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
