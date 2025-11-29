<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Support\CommunicationService;
use CodeIgniter\API\ResponseTrait;

/**
 * Ticket communications API.
 *
 * @agent-controller: TicketCommunications
 * @agent-pattern: Thin controller - routing only
 */
class TicketCommunicationsController extends BaseController
{
    use ResponseTrait;

    protected CommunicationService $service;

    public function __construct()
    {
        $this->service = service('communicationService');
    }

    /** @agent-use: POST /api/support-tickets/{ticketId}/communications */
    public function create($ticketId)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create((int) $ticketId, $payload)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
