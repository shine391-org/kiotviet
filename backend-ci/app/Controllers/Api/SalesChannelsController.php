<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\SalesChannels\SalesChannelService;
use CodeIgniter\API\ResponseTrait;

class SalesChannelsController extends BaseController
{
    use ResponseTrait;

    protected SalesChannelService $service;

    public function __construct()
    {
        $this->service = service('salesChannelService');
    }

    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->get((int) $id)));
    }

    public function create()
    {
        return $this->wrap(fn () => $this->respondCreated($this->service->create($this->safeInput())));
    }

    public function update($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $this->safeInput())));
    }

    public function delete($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->delete((int) $id)));
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
            log_message('error', 'SalesChannelsController error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->failServerError('An unexpected error occurred');
        }
    }

    private function safeInput(): array
    {
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) return $json;
        } catch (\Throwable $e) {}
        $raw = $this->request->getRawInput();
        return is_array($raw) ? $raw : [];
    }
}
