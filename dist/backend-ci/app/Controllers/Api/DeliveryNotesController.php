<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\DeliveryNotes\DeliveryNoteService;
use CodeIgniter\API\ResponseTrait;

/** Delivery notes API. @agent-controller: Delivery notes @agent-pattern: Thin controller - routing only */
class DeliveryNotesController extends BaseController
{
    use ResponseTrait;

    protected DeliveryNoteService $service;

    public function __construct()
    {
        $this->service = service('deliveryNoteService');
    }

    /** List delivery notes. @agent-use: GET /api/delivery-notes */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    /** Detail. @agent-use: GET /api/delivery-notes/{id} */
    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->show((int) $id)));
    }

    /** Create manually. @agent-use: POST /api/delivery-notes */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    /** Create from order. @agent-use: POST /api/delivery-notes/from-order */
    public function createFromOrder()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->createFromOrder($payload)));
    }

    /** Confirm. */
    public function confirm($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $userId = isset($payload['confirmed_by']) ? (int) $payload['confirmed_by'] : null;
        return $this->wrap(fn () => $this->respond($this->service->confirm((int) $id, $userId)));
    }

    /** Ship (tracking). */
    public function ship($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->ship((int) $id, $payload)));
    }

    /** Deliver items. */
    public function deliver($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->deliver((int) $id, $payload)));
    }

    /** Cancel. */
    public function cancel($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $userId = isset($payload['cancelled_by']) ? (int) $payload['cancelled_by'] : null;
        return $this->wrap(fn () => $this->respond($this->service->cancel((int) $id, $userId)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
