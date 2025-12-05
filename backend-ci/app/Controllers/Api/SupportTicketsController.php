<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Support\SupportTicketService;
use CodeIgniter\API\ResponseTrait;

/**
 * Support tickets API.
 *
 * @agent-controller: SupportTickets
 * @agent-pattern: Thin controller - routing only
 */
class SupportTicketsController extends BaseController
{
    use ResponseTrait;

    protected SupportTicketService $service;

    public function __construct()
    {
        $this->service = service('supportTicketService');
    }

    /** @agent-use: POST /api/support-tickets */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    /** @agent-use: PUT /api/support-tickets/{id} */
    public function update($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $payload)));
    }

    /** @agent-use: GET /api/support-tickets/{id} */
    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->get((int) $id)));
    }

    /** @agent-use: POST /api/support-tickets/{id}/status */
    public function updateStatus($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $status = $payload['status'] ?? '';
        return $this->wrap(fn () => $this->respond($this->service->changeStatus((int) $id, $status)));
    }

    /** @agent-use: POST /api/support-tickets/{id}/assign */
    public function assign($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $assignee = $payload['assigned_to'] ?? null;
        return $this->wrap(fn () => $this->respond($this->service->assign((int) $id, $assignee)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
