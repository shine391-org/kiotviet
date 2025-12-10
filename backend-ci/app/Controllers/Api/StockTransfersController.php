<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Inventory\StockTransferService;
use CodeIgniter\API\ResponseTrait;

class StockTransfersController extends BaseController
{
    use ResponseTrait;

    protected StockTransferService $service;

    public function __construct()
    {
        $this->service = new StockTransferService();
    }

    public function index()
    {
        return $this->wrap(fn() => $this->respond($this->service->list($this->request->getGet())));
    }

    public function show($code)
    {
        return $this->wrap(fn() => $this->respond($this->service->show($code)));
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        $payload['created_by'] = $this->getCurrentUserId();
        return $this->wrap(fn() => $this->respondCreated($this->service->create($payload)));
    }

    public function update($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn() => $this->respond($this->service->update((int) $id, $payload)));
    }

    public function submit($id)
    {
        return $this->wrap(fn() => $this->respond($this->service->submit((int) $id)));
    }

    public function open($code)
    {
        return $this->wrap(fn() => $this->respond($this->service->submitByCode($code)));
    }

    public function receive($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $payload['received_by'] = $this->getCurrentUserId();
        return $this->wrap(fn() => $this->respond($this->service->receive((int) $id, $payload)));
    }

    public function cancel($id)
    {
        return $this->wrap(fn() => $this->respond($this->service->cancel((int) $id)));
    }

    public function duplicate($code)
    {
        return $this->wrap(fn() => $this->respondCreated($this->service->duplicate($code)));
    }

    public function saveNotes($code)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $notes = $payload['receivingNotes'] ?? '';
        return $this->wrap(fn() => $this->respond($this->service->saveNotes($code, $notes)));
    }

    protected function getCurrentUserId(): ?int
    {
        $user = session()->get('user');
        return $user['id'] ?? null;
    }

    protected function wrap(callable $action)
    {
        try {
            return $action();
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->failNotFound($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', '[StockTransfersController] Unexpected error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->failServerError('Internal server error');
        }
    }
}
