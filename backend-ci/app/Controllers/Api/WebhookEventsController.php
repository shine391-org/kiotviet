<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Webhooks\WebhookDispatcher;
use CodeIgniter\API\ResponseTrait;

/** Webhook events API. @agent-controller: Webhook events @agent-pattern: Thin controller */
class WebhookEventsController extends BaseController
{
    use ResponseTrait;

    protected WebhookDispatcher $dispatcher;

    public function __construct()
    {
        $this->dispatcher = service('webhookDispatcher');
    }

    /** List events. @agent-use: GET /api/webhook-events */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->dispatcher->listEvents($this->request->getGet())));
    }

    /** Retry event delivery. @agent-use: POST /api/webhook-events/{id}/retry */
    public function retry($id)
    {
        return $this->wrap(fn () => $this->respond($this->dispatcher->retry((int) $id)));
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
