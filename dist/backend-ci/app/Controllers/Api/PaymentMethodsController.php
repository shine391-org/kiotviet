<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\PaymentMethods\PaymentMethodService;
use CodeIgniter\API\ResponseTrait;

/** Payment methods API. @agent-controller: Payment methods @agent-pattern: Thin controller - routing only */
class PaymentMethodsController extends BaseController
{
    use ResponseTrait;

    protected PaymentMethodService $service;

    public function __construct()
    {
        $this->service = service('paymentMethodService');
    }

    /** List methods. @agent-use: GET /api/payment-methods @agent-pattern: Standard list */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    /** Get detail. @agent-use: GET /api/payment-methods/{id} @agent-pattern: Thin get */
    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->get((int) $id)));
    }

    /** Create method. @agent-use: POST /api/payment-methods @agent-pattern: Thin create */
    public function create()
    {
        return $this->wrap(fn () => $this->respondCreated($this->service->create($this->safeInput())));
    }

    /** Update method. @agent-use: PUT /api/payment-methods/{id} @agent-pattern: Thin update */
    public function update($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $this->safeInput())));
    }

    /** Delete method. @agent-use: DELETE /api/payment-methods/{id} @agent-pattern: Soft delete */
    public function delete($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->delete((int) $id)));
    }

    /** Activate method. @agent-use: PATCH /api/payment-methods/{id}/activate @agent-pattern: Status toggle */
    public function activate($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->activate((int) $id)));
    }

    /** Deactivate method. @agent-use: PATCH /api/payment-methods/{id}/deactivate @agent-pattern: Status toggle */
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
            // ignore and fall back
        }

        $raw = $this->request->getRawInput();
        return is_array($raw) ? $raw : [];
    }
}
