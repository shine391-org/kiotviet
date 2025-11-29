<?php

namespace App\Services\Orders;

use App\Repositories\Orders\OrderRepository;
use App\Services\PriceLists\PriceCalculatorService;
use App\Services\Orders\OrderPaymentService;
use App\Services\Pricing\PricingService;
use App\Services\Inventory\InventoryMovementLogger;
use App\Repositories\Inventory\InventoryRepository;
use App\Services\Products\ProductBatchService;
use App\Services\Products\ProductSerialNumberService;
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
    protected PricingService $advancedPricing;
    protected OrderNumberGenerator $numberGen;
    protected ?WebhookDispatcher $webhooks;
    protected OrderPaymentService $paymentService;
    protected InventoryMovementLogger $movementLogger;
    protected InventoryRepository $inventoryRepo;
    protected ProductBatchService $batchService;
    protected ProductSerialNumberService $serialService;

    public function __construct(
        ?OrderRepository $orders = null,
        ?OrderValidator $validator = null,
        ?PriceCalculatorService $pricing = null,
        ?PricingService $advancedPricing = null,
        ?OrderCreateValidator $createValidator = null,
        ?OrderNumberGenerator $numberGen = null,
        ?WebhookDispatcher $webhooks = null,
        ?OrderPaymentService $paymentService = null,
        ?InventoryMovementLogger $movementLogger = null,
        ?InventoryRepository $inventoryRepo = null,
        ?ProductBatchService $batchService = null,
        ?ProductSerialNumberService $serialService = null
    ) {
        $this->orders = $orders ?? new OrderRepository();
        $this->validator = $validator ?? new OrderValidator();
        $this->createValidator = $createValidator ?? new OrderCreateValidator();
        $this->pricing = $pricing ?? new PriceCalculatorService();
        $this->advancedPricing = $advancedPricing ?? new PricingService();
        $this->numberGen = $numberGen ?? new OrderNumberGenerator();
        $this->webhooks = $webhooks;
        $this->paymentService = $paymentService ?? new OrderPaymentService();
        $this->movementLogger = $movementLogger ?? new InventoryMovementLogger();
        $this->inventoryRepo = $inventoryRepo ?? new InventoryRepository();
        $this->batchService = $batchService ?? new ProductBatchService();
        $this->serialService = $serialService ?? new ProductSerialNumberService();
    }

    /** Preview order totals with price lists applied. @agent-use: POST /api/orders/calculate-preview */
    public function preview(array $payload): array
    {
        $validated = $this->validator->validateOrder($payload);
        $groupId = $this->resolveCustomerGroup($validated['customer_id'], $validated['customer_group_id']);

        $items = [];
        $subtotal = 0; $total = 0; $firstPriceListId = null; $firstPriceListName = null;
        foreach ($validated['items'] as $item) {
            $this->assertStockAvailable($item);
            $calc = $this->advancedPricing->getPrice([
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'customer_id' => $validated['customer_id'],
                'project_id' => null,
                'quantity' => $item['quantity'],
                'order_date' => $validated['order_date'],
            ]);
            $items[] = [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'],
                'quantity' => $item['quantity'],
                'batch_id' => $item['batch_id'] ?? null,
                'serial_numbers' => $item['serial_numbers'] ?? [],
                'base_price' => $calc['base_price'],
                'final_price' => $calc['final_price'],
                'line_total' => $calc['line_total'],
                'applied_price_list_id' => $calc['applied_price_list_id'],
                'applied_price_list_name' => $calc['applied_price_list_name'],
                'pricing_reason' => $calc['reason'] ?? null,
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
                'batch_id' => $item['batch_id'] ?? null,
                'serial_numbers' => ! empty($item['serial_numbers']) ? json_encode($item['serial_numbers']) : null,
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
        foreach ($order['items'] as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $variantId = $item['variant_id'] ?? null;
            $qty = (float) ($item['quantity'] ?? 0);
            if ($productId <= 0 || $qty <= 0) {
                continue;
            }
            $serials = $this->serialsFromItem($item);
            $batchId = isset($item['batch_id']) ? (int) $item['batch_id'] : null;

            if ($batchId) {
                $this->batchService->adjustQuantity($batchId, [
                    'quantity_delta' => -$qty,
                    'reference_type' => 'order',
                    'reference_id' => $order['id'] ?? null,
                    'reason' => 'POS auto-complete deduction',
                    'branch_id' => $branchId,
                    'warehouse_id' => $branchId,
                    'serial_number' => $serials ? implode(',', $serials) : null,
                    'movement_type' => 'sale',
                ]);
            } else {
                $this->inventoryRepo->adjustStockWithLock($productId, $variantId ? (int) $variantId : null, $branchId, -$qty, $branchId);
                $this->movementLogger->log(
                    branchId: $branchId,
                    productId: $productId,
                    variantId: $variantId ? (int) $variantId : null,
                    type: 'sale',
                    quantity: -$qty,
                    batchId: null,
                    serialNumber: $serials ? implode(',', $serials) : null,
                    referenceType: 'order',
                    referenceId: $order['id'] ?? null,
                    notes: 'POS auto-complete deduction',
                    createdBy: $order['created_by'] ?? null
                );
            }

            if (! empty($serials)) {
                $this->serialService->sell([
                    'serial_numbers' => $serials,
                    'order_id' => (int) ($order['id'] ?? 0),
                ]);
            }
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

    private function assertStockAvailable(array $item): void
    {
        $productId = (int) ($item['product_id'] ?? 0);
        $variantId = $item['variant_id'] ?? null;
        $qty = (float) ($item['quantity'] ?? 0);
        $serials = $this->serialsFromItem($item);
        $batchId = isset($item['batch_id']) ? (int) $item['batch_id'] : null;

        if ($batchId) {
            $batch = $this->batchService->show($batchId)['data'] ?? null;
            if (! $batch) {
                throw new RuntimeException('Batch not found');
            }
            if ((int) $batch['product_id'] !== $productId) {
                throw new RuntimeException('Batch does not belong to product');
            }
            if ($variantId && isset($batch['variant_id']) && (int) $batch['variant_id'] !== (int) $variantId) {
                throw new RuntimeException('Batch does not belong to variant');
            }
            if ((float) ($batch['current_quantity'] ?? 0) < $qty) {
                throw new RuntimeException('Insufficient batch quantity');
            }
        }

        foreach ($serials as $serial) {
            $this->serialService->ensureAvailableForOrder($serial, $item['order_id'] ?? null);
        }

        if ($productId <= 0 || $qty <= 0) {
            return;
        }

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

    private function serialsFromItem(array $item): array
    {
        $serials = $item['serial_numbers'] ?? [];
        if (is_string($serials)) {
            $decoded = json_decode($serials, true);
            if (is_array($decoded)) {
                $serials = $decoded;
            } else {
                $serials = array_filter(array_map('trim', explode(',', $serials)));
            }
        }
        if (! is_array($serials)) {
            return [];
        }
        $serials = array_map(static fn ($s) => is_numeric($s) ? (string) $s : (is_string($s) ? trim($s) : ''), $serials);
        $serials = array_filter($serials, static fn ($s) => $s !== '');
        return array_values(array_unique($serials));
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
