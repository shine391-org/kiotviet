<?php

namespace App\Services\Orders;

use App\Repositories\Orders\OrderRepository;
use App\Services\PriceLists\PriceCalculatorService;
use App\Services\Orders\OrderPaymentService;
use App\Validators\OrderValidator;
use App\Validators\OrderCreateValidator;
use App\Services\Webhooks\WebhookDispatcher;
use Config\Database;
use RuntimeException;

/** Order creation + pricing. @agent-service: Orders @agent-pattern: Service orchestrator @agent-reusable: MEDIUM */
class OrderService
{
    protected OrderRepository $orders;
    protected OrderValidator $validator;
    protected OrderCreateValidator $createValidator;
    protected PriceCalculatorService $pricing;
    protected OrderNumberGenerator $numberGen;
    protected ?WebhookDispatcher $webhooks;
    protected OrderPaymentService $paymentService;

    public function __construct(
        ?OrderRepository $orders = null,
        ?OrderValidator $validator = null,
        ?PriceCalculatorService $pricing = null,
        ?OrderCreateValidator $createValidator = null,
        ?OrderNumberGenerator $numberGen = null,
        ?WebhookDispatcher $webhooks = null,
        ?OrderPaymentService $paymentService = null
    ) {
        $this->orders = $orders ?? new OrderRepository();
        $this->validator = $validator ?? new OrderValidator();
        $this->createValidator = $createValidator ?? new OrderCreateValidator();
        $this->pricing = $pricing ?? new PriceCalculatorService();
        $this->numberGen = $numberGen ?? new OrderNumberGenerator();
        $this->webhooks = $webhooks;
        $this->paymentService = $paymentService ?? new OrderPaymentService();
    }

    /** Preview order totals with price lists applied. @agent-use: POST /api/orders/calculate-preview */
    public function preview(array $payload): array
    {
        $validated = $this->validator->validateOrder($payload);
        $groupId = $this->resolveCustomerGroup($validated['customer_id'], $validated['customer_group_id']);

        $items = [];
        $subtotal = 0; $total = 0; $firstPriceListId = null; $firstPriceListName = null;
        foreach ($validated['items'] as $item) {
            $this->assertStockAvailable($item['product_id'], $item['variant_id'], $item['quantity']);
            $calc = $this->pricing->getProductPrice($item['product_id'], $item['variant_id'], $groupId, $item['quantity'], $validated['order_date']);
            $items[] = [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'quantity' => $item['quantity'],
                'base_price' => $calc['base_price'],
                'final_price' => $calc['final_price'],
                'line_total' => $calc['line_total'],
                'applied_price_list_id' => $calc['applied_price_list_id'],
                'applied_price_list_name' => $calc['applied_price_list_name'],
            ];
            $subtotal += $calc['base_price'] * $item['quantity'];
            $total += $calc['final_price'] * $item['quantity'];
            if (! $firstPriceListId && $calc['applied_price_list_id']) {
                $firstPriceListId = $calc['applied_price_list_id'];
                $firstPriceListName = $calc['applied_price_list_name'];
            }
        }
        $discount = $subtotal - $total;

        return [
            'success' => true,
            'data' => [
                'customer_id' => $validated['customer_id'],
                'customer_group_id' => $groupId,
                'order_date' => $validated['order_date'],
                'items' => $items,
                'subtotal' => round($subtotal, 2),
                'discount_total' => round($discount, 2),
                'total' => round($total, 2),
                'applied_price_list_id' => $firstPriceListId,
                'applied_price_list_name' => $firstPriceListName,
            ],
        ];
    }

    /** Create order (persists) after pricing. */
    public function create(array $payload): array
    {
        $validated = $this->createValidator->validate($payload);
        $preview = $this->preview($validated);
        $data = $preview['data'];

        $orderNumber = $this->numberGen->generate();
        $paidAmount = $validated['paid_amount'];
        $shippingFee = $validated['shipping_fee'];
        $totalWithShipping = $data['total'] + $shippingFee;
        $debt = max(0, round($totalWithShipping - $paidAmount, 2));
        $isPaid = abs($debt) < 0.01;

        $orderPayload = [
            'order_number' => $orderNumber,
            'customer_id' => $data['customer_id'],
            'customer_group_id' => $data['customer_group_id'],
            'branch_id' => $validated['branch_id'],
            'order_date' => $data['order_date'],
            'order_type' => $validated['order_type'],
            'payment_method' => $validated['payment_method'],
            'status' => $validated['order_type'] === 'pos' ? 'completed' : 'draft',
            'subtotal' => $data['subtotal'],
            'discount_total' => $data['discount_total'],
            'shipping_fee' => $shippingFee,
            'total' => $totalWithShipping,
            'paid_amount' => $paidAmount,
            'debt_amount' => $debt,
            'is_paid' => $isPaid ? 1 : 0,
            'applied_price_list_id' => $data['applied_price_list_id'],
            'shipping_name' => $validated['shipping']['name'],
            'shipping_phone' => $validated['shipping']['phone'],
            'shipping_address' => $validated['shipping']['address'],
            'shipping_ward' => $validated['shipping']['ward'],
            'shipping_district' => $validated['shipping']['district'],
            'shipping_city' => $validated['shipping']['city'],
            'notes' => $validated['notes'],
        ];

        $itemRows = [];
        foreach ($data['items'] as $item) {
            $itemRows[] = [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'quantity' => $item['quantity'],
                'base_price' => $item['base_price'],
                'final_price' => $item['final_price'],
                'price_list_id' => $item['applied_price_list_id'],
                'price_list_name' => $item['applied_price_list_name'],
            ];
        }

        $order = $this->orders->create($orderPayload, $itemRows);
        $order['items'] = $itemRows;

        // Handle multiple payments if provided
        if (!empty($validated['payments']) && is_array($validated['payments'])) {
            foreach ($validated['payments'] as $payment) {
                $this->paymentService->addPayment([
                    'order_id' => $order['id'],
                    'payment_method' => $payment['payment_method'],
                    'amount' => $payment['amount'],
                    'created_by' => $validated['created_by'] ?? 1,
                ]);
            }
            // Refresh order totals after payments
            $orderRefreshed = $this->orders->findById($order['id']);
            if ($orderRefreshed) {
                $order = array_merge($order, $orderRefreshed);
            }
        }

        // POS đơn hàng auto hoàn tất -> trừ tồn ngay
        if ($validated['order_type'] === 'pos') {
            $this->deductPosInventory($order, (int) ($validated['branch_id'] ?? 0));
            $this->logPosStatus($order);
        }

        $this->emit('order.created', $order);
        return ['success' => true, 'data' => $order];
    }

    /**
     * Deduct inventory for POS orders and log movement.
     */
    private function deductPosInventory(array $order, int $branchId): void
    {
        if ($branchId <= 0 || empty($order['items'])) {
            return;
        }
        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        foreach ($order['items'] as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $variantId = $item['variant_id'] ?? null;
            $qty = (float) ($item['quantity'] ?? 0);
            if ($productId <= 0 || $qty <= 0) {
                continue;
            }

            // Update stock using null-safe comparison to handle NULL variant_id
            $updated = $db->query(
                "UPDATE inventory_stock 
                 SET quantity_on_hand = quantity_on_hand - ?
                 WHERE branch_id = ? AND product_id = ? AND (variant_id <=> ?)
                 LIMIT 1",
                [$qty, $branchId, $productId, $variantId]
            );

            // Fallback: try null variant when variant-specific row not found
            if ($db->affectedRows() === 0 && $variantId !== null) {
                $db->query(
                    "UPDATE inventory_stock 
                     SET quantity_on_hand = quantity_on_hand - ?
                     WHERE branch_id = ? AND product_id = ? AND variant_id IS NULL
                     LIMIT 1",
                    [$qty, $branchId, $productId]
                );
            }

            // Log movement
            $db->table('inventory_movements')->insert([
                'branch_id' => $branchId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'type' => 'sale',
                'quantity' => -$qty,
                'reference_type' => 'order',
                'reference_id' => $order['id'] ?? null,
                'notes' => 'POS auto-complete deduction',
                'created_by' => $order['created_by'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function resolveCustomerGroup(?int $customerId, ?int $providedGroupId): ?int
    {
        if ($providedGroupId) { return $providedGroupId; }
        if (! $customerId) { return null; }

        $db = Database::connect();
        if (! $db->tableExists('customers')) { return null; }
        $row = $db->table('customers')->select('customer_group_id')->where('id', $customerId)->get()->getRowArray();
        if (! $row) {
            throw new \InvalidArgumentException('Customer not found');
        }
        return $row['customer_group_id'] ?? null;
    }

    private function assertStockAvailable(int $productId, ?int $variantId, float $qty): void
    {
        $db = Database::connect();
        if (! $db->tableExists('inventory_stock')) { return; }
        $row = $db->table('inventory_stock')
            ->select('quantity_on_hand, quantity_reserved')
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->get()->getRowArray();
        if (! $row) { return; } // no stock record, allow
        $available = (float) ($row['quantity_on_hand'] ?? 0) - (float) ($row['quantity_reserved'] ?? 0);
        if ($available < $qty) {
            throw new \RuntimeException('Insufficient stock for product');
        }
    }

    private function emit(string $event, array $payload): void
    {
        if (! $this->webhooks) {
            return;
        }
        try {
            $this->webhooks->dispatch($event, $payload);
        } catch (\Throwable $e) {
            log_message('error', 'Webhook dispatch failed: ' . $e->getMessage());
        }
    }

    /**
     * Log auto-complete status change for POS orders.
     */
    private function logPosStatus(array $order): void
    {
        $db = Database::connect();
        if (! $db->tableExists('order_status_logs')) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $db->table('order_status_logs')->insert([
            'order_id' => $order['id'] ?? null,
            'from_status' => null,
            'to_status' => 'completed',
            'notes' => 'POS auto complete',
            'changed_by' => $order['created_by'] ?? null,
            'changed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
