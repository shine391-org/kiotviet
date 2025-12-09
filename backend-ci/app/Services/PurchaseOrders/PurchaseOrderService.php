<?php

namespace App\Services\PurchaseOrders;

use App\Repositories\PurchaseOrders\PurchaseOrderRepository;
use App\Validators\PurchaseOrderValidator;
use RuntimeException;

/**
 * @agent-service: Purchase orders
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class PurchaseOrderService
{
    protected PurchaseOrderRepository $repo;
    protected PurchaseOrderValidator $validator;

    public function __construct(?PurchaseOrderRepository $repo = null, ?PurchaseOrderValidator $validator = null)
    {
        $this->repo = $repo ?? new PurchaseOrderRepository();
        $this->validator = $validator ?? new PurchaseOrderValidator();
    }

    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $number = $this->repo->nextNumber();
        $total = array_sum(array_column($data['items'], 'amount'));
        $discount = $data['discount'] ?? 0;
        
        $orderRow = [
            'order_number' => $number,
            'po_number' => $number,
            'branch_id' => $data['branch_id'],
            'partner_id' => $data['partner_id'] ?? $data['supplier_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? $data['partner_id'] ?? null,
            'payment_method' => $data['payment_method'],
            'total' => $total - $discount,
            'total_amount' => $total,
            'subtotal' => $total,
            'order_date' => $data['order_date'],
            'notes' => $data['notes'],
            'status' => $data['status'] ?? 'draft',
            'payment_status' => 'unpaid',
            'created_by' => auth()->id() ?? null,
        ];
        
        $order = $this->repo->create($orderRow, $data['items']);
        return ['success' => true, 'data' => $order];
    }

    /**
     * List purchase orders with filters.
     */
    public function list(array $filters = []): array
    {
        return $this->repo->list($filters);
    }

    public function submit(int $id): array
    {
        $order = $this->repo->findById($id);
        if (! $order) {
            throw new RuntimeException('Purchase order not found');
        }
        if ($order['status'] !== 'draft') {
            throw new RuntimeException('Only draft orders can be submitted');
        }
        $this->repo->updateStatus($id, 'submitted');
        return ['success' => true, 'data' => $this->repo->findById($id)];
    }

    public function cancel(int $id): array
    {
        $order = $this->repo->findById($id);
        if (! $order) {
            throw new RuntimeException('Purchase order not found');
        }
        $this->repo->updateStatus($id, 'cancelled');
        return ['success' => true, 'data' => $this->repo->findById($id)];
    }

    public function get(int $id): array
    {
        $order = $this->repo->findById($id);
        if (! $order) {
            throw new RuntimeException('Purchase order not found');
        }
        return ['success' => true, 'data' => $order];
    }
}
