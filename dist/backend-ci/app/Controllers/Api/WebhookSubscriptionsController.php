<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Webhooks\WebhookSubscriptionService;
use CodeIgniter\API\ResponseTrait;

/** Webhook subscriptions API. @agent-controller: Webhooks @agent-pattern: Thin controller */
class WebhookSubscriptionsController extends BaseController
{
    use ResponseTrait;

    protected WebhookSubscriptionService $service;

    public function __construct()
    {
        $this->service = service('webhookSubscriptionService');
    }

    /** List subscriptions. @agent-use: GET /api/webhooks/subscriptions */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    /** Create subscription. @agent-use: POST /api/webhooks/subscriptions */
    public function create()
    {
        return $this->wrap(fn () => $this->respondCreated($this->service->create($this->safeInput())));
    }

    /** Update subscription. @agent-use: PUT /api/webhooks/subscriptions/{id} */
    public function update($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $this->safeInput())));
    }

    /** Activate subscription. @agent-use: PATCH /api/webhooks/subscriptions/{id}/activate */
    public function activate($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->activate((int) $id)));
    }

    /** Deactivate subscription. @agent-use: PATCH /api/webhooks/subscriptions/{id}/deactivate */
    public function deactivate($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->deactivate((int) $id)));
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

    private function safeInput(): array
    {
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) {
                return $json;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        $raw = $this->request->getRawInput();
        return is_array($raw) ? $raw : [];
    }
}
