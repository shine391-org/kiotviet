<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\PurchaseOrders\PurchaseOrderService;
use CodeIgniter\API\ResponseTrait;

/**
 * Purchase orders API.
 *
 * @agent-controller: PurchaseOrders
 * @agent-pattern: Thin controller - routing only
 */
class PurchaseOrdersController extends BaseController
{
    use ResponseTrait;

    protected PurchaseOrderService $service;

    public function __construct()
    {
        $this->service = service('purchaseOrderService');
    }

    /** @agent-use: GET /api/purchase-orders */
    public function index()
    {
        $filters = [
            'branch_id' => $this->request->getGet('branch_id'),
            'status' => $this->request->getGet('status'),
            'supplier_id' => $this->request->getGet('supplier_id'),
            'created_by' => $this->request->getGet('created_by'),
            'receiver_id' => $this->request->getGet('receiver_id'),
            'date_from' => $this->request->getGet('date_from'),
            'date_to' => $this->request->getGet('date_to'),
            'search' => $this->request->getGet('search'),
            'page' => $this->request->getGet('page') ?? 1,
            'limit' => $this->request->getGet('limit') ?? 15,
        ];
        // Filter out null values
        $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');
        return $this->wrap(fn () => $this->respond($this->service->list($filters)));
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    public function submit($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->submit((int) $id)));
    }

    public function cancel($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->cancel((int) $id)));
    }

    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->get((int) $id)));
    }

    /** @agent-use: GET /api/purchase-orders/export */
    public function export()
    {
        return $this->wrap(function () {
            $filters = $this->request->getGet() ?? [];
            $exportService = new \App\Services\PurchaseOrders\PurchaseOrderExportService();
            $filepath = $exportService->exportOrders($filters);

            return $this->response
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="purchase_orders_' . date('Ymd_His') . '.xlsx"')
                ->setBody(file_get_contents($filepath));
        });
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
