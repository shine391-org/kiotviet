<?php

namespace App\Services\POS;

use App\Services\Returns\ReturnService;
use App\Repositories\POS\POSSalesRepository;
use InvalidArgumentException;
use RuntimeException;

/**
 * POS Sales business logic - quick return, daily report, sellers
 *
 * @agent-service: POSSales
 * @agent-pattern: Service orchestrator
 */
class POSSalesService
{
    protected POSSalesRepository $repo;
    protected ReturnService $returnService;

    public function __construct(
        ?POSSalesRepository $repo = null,
        ?ReturnService $returnService = null
    ) {
        $this->repo = $repo ?? new POSSalesRepository();
        $this->returnService = $returnService ?? new ReturnService();
    }

    /**
     * Quick return from invoice - simplified flow for POS
     * 
     * @param array $payload {invoice_id, items: [{product_id, quantity, reason}], refund_method, notes}
     */
    public function quickReturn(array $payload): array
    {
        $invoiceId = (int) ($payload['invoice_id'] ?? 0);
        if ($invoiceId <= 0) {
            throw new InvalidArgumentException('invoice_id is required');
        }

        // Get invoice with order info
        $invoice = $this->repo->findInvoiceById($invoiceId);
        if (!$invoice) {
            throw new RuntimeException('Invoice not found');
        }

        // Get linked order
        $orderId = $this->repo->findOrderIdByInvoice($invoiceId);
        if (!$orderId) {
            throw new InvalidArgumentException('No order linked to this invoice');
        }

        $order = $this->repo->findOrderWithItems($orderId);
        if (!$order) {
            throw new RuntimeException('Order not found');
        }

        // Build return items
        $returnItems = $this->buildQuickReturnItems($payload['items'] ?? [], $order);
        if (empty($returnItems)) {
            throw new InvalidArgumentException('At least one item is required for return');
        }

        // Create return via ReturnService
        $returnPayload = [
            'order_id' => $orderId,
            'customer_id' => $order['customer_id'] ?? 0,
            'items' => $returnItems,
            'reason' => $payload['reason'] ?? 'customer_request',
            'reason_detail' => $payload['notes'] ?? null,
            'refund_method' => $payload['refund_method'] ?? 'cash',
            'refund_shipping_fee' => $payload['refund_shipping_fee'] ?? false,
            'created_by' => $payload['created_by'] ?? null,
        ];

        $result = $this->returnService->create($returnPayload);

        // Auto-approve if requested
        if (($payload['auto_approve'] ?? false) && $result['success']) {
            $returnId = $result['data']['id'] ?? 0;
            if ($returnId > 0) {
                $approvePayload = [
                    'user_id' => $payload['created_by'] ?? 1,
                    'refund_method' => $payload['refund_method'] ?? 'cash',
                    'refund_shipping_fee' => $payload['refund_shipping_fee'] ?? false,
                    'notes' => 'Quick return from POS',
                    'version' => 1,
                ];
                $result = $this->returnService->approve($returnId, $approvePayload);

                // Auto-complete after approve
                if ($result['success']) {
                    $completePayload = [
                        'user_id' => $payload['created_by'] ?? 1,
                        'version' => 2,
                    ];
                    $result = $this->returnService->complete($returnId, $completePayload);
                }
            }
        }

        return $result;
    }

    /**
     * Get daily report for POS
     * 
     * @param array $filters {branch_id, date, user_id}
     */
    public function dailyReport(array $filters): array
    {
        $branchId = (int) ($filters['branch_id'] ?? 0);
        $date = $filters['date'] ?? date('Y-m-d');
        $userId = isset($filters['user_id']) ? (int) $filters['user_id'] : null;

        // Sales summary
        $salesSummary = $this->repo->getSalesSummary($branchId, $date, $userId);

        // Returns summary
        $returnsSummary = $this->repo->getReturnsSummary($branchId, $date, $userId);

        // Payment methods breakdown
        $paymentBreakdown = $this->repo->getPaymentBreakdown($branchId, $date, $userId);

        // Top products sold
        $topProducts = $this->repo->getTopProducts($branchId, $date, 10);

        // Hourly sales chart data
        $hourlySales = $this->repo->getHourlySales($branchId, $date);

        // Cash drawer movements
        $cashMovements = $this->repo->getCashMovements($branchId, $date);

        return [
            'success' => true,
            'data' => [
                'date' => $date,
                'branch_id' => $branchId,
                'sales' => [
                    'total_orders' => (int) ($salesSummary['total_orders'] ?? 0),
                    'total_items' => (int) ($salesSummary['total_items'] ?? 0),
                    'gross_sales' => (float) ($salesSummary['gross_sales'] ?? 0),
                    'discounts' => (float) ($salesSummary['discounts'] ?? 0),
                    'net_sales' => (float) ($salesSummary['net_sales'] ?? 0),
                    'average_order_value' => (float) ($salesSummary['average_order_value'] ?? 0),
                ],
                'returns' => [
                    'total_returns' => (int) ($returnsSummary['total_returns'] ?? 0),
                    'return_amount' => (float) ($returnsSummary['return_amount'] ?? 0),
                    'refund_amount' => (float) ($returnsSummary['refund_amount'] ?? 0),
                ],
                'payments' => $paymentBreakdown,
                'top_products' => $topProducts,
                'hourly_sales' => $hourlySales,
                'cash_drawer' => [
                    'opening_balance' => (float) ($cashMovements['opening_balance'] ?? 0),
                    'cash_in' => (float) ($cashMovements['cash_in'] ?? 0),
                    'cash_out' => (float) ($cashMovements['cash_out'] ?? 0),
                    'expected_balance' => (float) ($cashMovements['expected_balance'] ?? 0),
                ],
            ],
        ];
    }

    /**
     * Get sellers for POS dropdown
     * 
     * @param array $filters {branch_id, search, status}
     */
    public function getSellers(array $filters): array
    {
        $branchId = isset($filters['branch_id']) ? (int) $filters['branch_id'] : null;
        $search = $filters['search'] ?? null;
        $status = $filters['status'] ?? 'active';

        $sellers = $this->repo->findSellers($branchId, $search, $status);

        return [
            'success' => true,
            'data' => array_map(fn($s) => [
                'id' => (int) $s['id'],
                'name' => $s['full_name'] ?? $s['username'],
                'username' => $s['username'],
                'phone' => $s['phone'] ?? null,
                'branch_id' => isset($s['branch_id']) ? (int) $s['branch_id'] : null,
            ], $sellers),
            'total' => count($sellers),
        ];
    }

    /**
     * Build return items from quick return payload
     */
    private function buildQuickReturnItems(array $items, array $order): array
    {
        $orderItemsMap = [];
        foreach ($order['items'] ?? [] as $item) {
            $key = $item['product_id'] . '-' . ($item['variant_id'] ?? '0');
            $orderItemsMap[$key] = $item;
        }

        $returnItems = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $variantId = (int) ($item['variant_id'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $key = $productId . '-' . $variantId;
            if (!isset($orderItemsMap[$key])) {
                throw new InvalidArgumentException("Product {$productId} not found in original order");
            }

            $orderItem = $orderItemsMap[$key];
            $returnItems[] = [
                'order_item_id' => (int) $orderItem['id'],
                'quantity_returned' => $quantity,
                'item_condition' => $item['condition'] ?? 'good',
            ];
        }

        return $returnItems;
    }
}
