<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Accounting\PurchaseInvoiceService;
use CodeIgniter\API\ResponseTrait;

/**
 * Purchase invoices API.
 *
 * @agent-controller: PurchaseInvoices
 * @agent-pattern: Thin controller - routing only
 */
class PurchaseInvoicesController extends BaseController
{
    use ResponseTrait;

    protected PurchaseInvoiceService $service;

    public function __construct()
    {
        $this->service = service('purchaseInvoiceService');
    }

    /** @agent-use: POST /api/purchase-invoices/preview */
    public function preview()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->preview($payload)));
    }

    /** @agent-use: POST /api/purchase-invoices */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    /** @agent-use: POST /api/purchase-invoices/{id}/submit */
    public function submit($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->submit((int) $id)));
    }

    /** @agent-use: POST /api/purchase-invoices/{id}/cancel */
    public function cancel($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->cancel((int) $id)));
    }

    /** @agent-use: GET /api/purchase-invoices/{id} */
    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->get((int) $id)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
