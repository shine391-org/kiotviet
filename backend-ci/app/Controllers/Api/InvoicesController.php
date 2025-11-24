<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Invoices\InvoiceService;
use CodeIgniter\API\ResponseTrait;

/** Invoices API. @agent-controller: Invoices @agent-pattern: Thin controller - routing only */
class InvoicesController extends BaseController
{
    use ResponseTrait;

    protected InvoiceService $service;

    public function __construct()
    {
        $this->service = service('invoiceService');
    }

    /** List invoices. @agent-use: GET /api/invoices */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    /** Get detail. @agent-use: GET /api/invoices/{id} */
    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->get((int) $id)));
    }

    /** Create invoice. @agent-use: POST /api/invoices */
    public function create()
    {
        return $this->wrap(fn () => $this->respondCreated($this->service->create($this->safeInput())));
    }

    /** Generate invoice from orders. @agent-use: POST /api/invoices/generate */
    public function generate()
    {
        return $this->wrap(fn () => $this->respondCreated($this->service->generateFromOrders($this->safeInput())));
    }

    /** Generate PDF. @agent-use: POST /api/invoices/{id}/pdf */
    public function generatePdf($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->generatePdf((int) $id)));
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
        }
        $raw = $this->request->getRawInput();
        return is_array($raw) ? $raw : [];
    }
}
