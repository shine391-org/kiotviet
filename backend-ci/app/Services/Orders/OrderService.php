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
use App\Services\POS\POSPaymentSplitService;
use App\Services\POS\POSProfileService;
use App\Services\POS\POSShiftService;
use App\Services\Coupons\CouponService;
use App\Services\Loyalty\LoyaltyService;
use App\Validators\OrderValidator;
use App\Validators\OrderCreateValidator;
use App\Services\Webhooks\WebhookDispatcher;
use CodeIgniter\Database\BaseConnection;
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
    protected POSProfileService $posProfiles;
    protected POSPaymentSplitService $paymentSplit;
    protected POSShiftService $shiftService;
    protected CouponService $couponService;
    protected LoyaltyService $loyaltyService;
    protected \App\Services\Payments\PaymentEntryService $paymentEntries;
    protected \App\Services\POS\POSTaxService $taxService;
    protected BaseConnection $db;

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
        ?ProductSerialNumberService $serialService = null,
        ?POSProfileService $posProfiles = null,
        ?POSPaymentSplitService $paymentSplit = null,
        ?POSShiftService $shiftService = null,
        ?CouponService $couponService = null,
        ?LoyaltyService $loyaltyService = null,
        ?\App\Services\Payments\PaymentEntryService $paymentEntries = null,
        ?\App\Services\POS\POSTaxService $taxService = null,
        ?BaseConnection $db = null
    ) {
        $this->db = $db ?? Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->orders = $orders ?? new OrderRepository(null, null, $this->db);
        $this->validator = $validator ?? new OrderValidator();
        $this->createValidator = $createValidator ?? new OrderCreateValidator();
        $this->pricing = $pricing ?? new PriceCalculatorService(null, null, $this->db);
        $this->advancedPricing = $advancedPricing ?? new PricingService(null, null, null, null, null, $this->db);
        $this->numberGen = $numberGen ?? new OrderNumberGenerator();
        $this->webhooks = $webhooks;
        $this->paymentService = $paymentService ?? new OrderPaymentService(null, null, null, null, $this->db);
        $this->movementLogger = $movementLogger ?? new InventoryMovementLogger();
        $this->inventoryRepo = $inventoryRepo ?? new InventoryRepository($this->db);
        $this->batchService = $batchService ?? new ProductBatchService();
        $this->serialService = $serialService ?? new ProductSerialNumberService();
        $this->posProfiles = $posProfiles ?? new POSProfileService();
        $this->paymentSplit = $paymentSplit ?? new POSPaymentSplitService();
        $this->couponService = $couponService ?? new CouponService(new \App\Repositories\Coupons\CouponRepository(null, null, $this->db));
        $this->loyaltyService = $loyaltyService ?? new LoyaltyService(new \App\Repositories\Loyalty\LoyaltyRepository(null, null, null, $this->db));
        $this->paymentEntries = $paymentEntries ?? new \App\Services\Payments\PaymentEntryService(
            new \App\Repositories\Payments\PaymentEntryRepository(null, $this->db),
            new \App\Validators\PaymentEntryValidator()
        );
        $this->taxService = $taxService ?? new \App\Services\POS\POSTaxService(
            new \App\Repositories\Taxes\TaxTemplateRepository(null, $this->db),
            new \App\Repositories\Taxes\TaxChargeRepository(null, $this->db)
        );
        if ($shiftService) {
            $this->shiftService = $shiftService;
        } else {
            $shiftRepo = new \App\Repositories\POS\POSShiftRepository(
                null,
                null,
                new \App\Repositories\POS\POSShiftPaymentRepository(null, $this->db),
                $this->db
            );
            $this->shiftService = new POSShiftService($shiftRepo, new \App\Validators\POSShiftValidator());
        }
    }

    /** Preview order totals with price lists applied. @agent-use: POST /api/orders/calculate-preview */
    public function preview(array $payload): array
    {
        $validated = $this->validator->validateOrder($payload);
        $groupId = $this->resolveCustomerGroup($validated['customer_id'], $validated['customer_group_id']);
        $priceListId = $validated['price_list_id'] ?? null;

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
                'price_list_id' => $priceListId,
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
        $profile = null;
        $shift = null;
        $posTablesAvailable = $this->db->tableExists('pos_profiles');

        if ($validated['order_type'] === 'pos' && ($posTablesAvailable || ! empty($validated['pos_profile_id']))) {
            $userId = (int) ($validated['user_id'] ?? 0);
            if ($userId <= 0 && empty($validated['pos_profile_id'])) {
                throw new \InvalidArgumentException('user_id is required for POS orders');
            }
            try {
                $resolved = $this->posProfiles->resolve([
                    'profile_id' => $validated['pos_profile_id'] ?? null,
                    'user_id' => $userId,
                    'branch_id' => $validated['branch_id'],
                ]);
                $profile = $resolved['data'] ?? null;
            } catch (\Throwable $e) {
                if ($posTablesAvailable) {
                    throw $e;
                }
            }
            if ($profile && ! empty($profile['branch_id']) && (int) $profile['branch_id'] !== (int) $validated['branch_id']) {
                throw new \InvalidArgumentException('POS profile branch mismatch');
            }
            if ($profile && ! empty($profile['price_list_id'])) {
                $validated['price_list_id'] = (int) $profile['price_list_id'];
            }
            if ($profile) {
                $validated['pos_profile_id'] = $profile['id'] ?? ($validated['pos_profile_id'] ?? null);

                if (! empty($profile['require_shift'])) {
                    $shift = $this->shiftService->requireOpenShift($userId ?: (int) ($profile['user_id'] ?? 0), $validated['branch_id']);
                }
            }
        }
        $preview = $this->preview($validated);
        $data = $preview['data'];

        $orderNumber = $this->numberGen->generate();
        $shippingFee = $validated['shipping_fee'];
        $totalWithShipping = $data['total'] + $shippingFee;

        $couponDiscount = 0.0;
        $coupon = null;
        if (! empty($validated['coupon_code'])) {
            $couponResult = $this->couponService->apply($validated['coupon_code'], $totalWithShipping);
            $coupon = $couponResult['coupon'];
            $couponDiscount = $couponResult['discount'];
        }

        $loyaltyDiscount = 0.0;
        $redeemedPoints = 0;
        if (! empty($validated['redeem_points'])) {
            if (empty($validated['customer_id'])) {
                throw new \InvalidArgumentException('customer_id required for redeeming points');
            }
            $redeemResult = $this->loyaltyService->previewRedeem((int) $validated['customer_id'], (int) $validated['redeem_points']);
            $loyaltyDiscount = min($redeemResult['discount'], $totalWithShipping - $couponDiscount);
            $redeemedPoints = $redeemResult['points_used'];
        }

        $totalWithShipping = max(0, $totalWithShipping - $couponDiscount - $loyaltyDiscount);
        $taxResult = $this->taxService->apply($profile['tax_template_id'] ?? ($validated['tax_template_id'] ?? null), $totalWithShipping);
        $totalWithShipping = $taxResult['grand_total'];
        $payments = $validated['payments'] ?? [];
        if ($validated['order_type'] === 'pos' && $profile) {
            $payments = $this->paymentSplit->validate($payments, $profile['payment_methods'] ?? [], $totalWithShipping);
        } elseif ($validated['order_type'] === 'pos' && ! empty($payments)) {
            $totalPay = array_sum(array_column($payments, 'amount'));
            if (abs($totalPay - $totalWithShipping) > 0.01) {
                throw new \InvalidArgumentException('Total payment amount must equal order total');
            }
        }
        $paidAmount = ! empty($payments) ? array_sum(array_column($payments, 'amount')) : $validated['paid_amount'];
        $paymentMethod = $validated['payment_method'] ?: ($payments[0]['payment_method'] ?? null);
        $initialPaid = ! empty($payments) ? 0.0 : $paidAmount;
        $initialDebt = max(0, round($totalWithShipping - $initialPaid, 2));
        $isPaid = abs($initialDebt) < 0.01;

        $orderPayload = [
            'order_number' => $orderNumber,
            'customer_id' => $data['customer_id'],
            'customer_group_id' => $data['customer_group_id'],
            'branch_id' => $validated['branch_id'],
            'warehouse_id' => $profile['warehouse_id'] ?? null,
            'order_date' => $data['order_date'],
            'order_type' => $validated['order_type'],
            'pos_profile_id' => $validated['pos_profile_id'] ?? null,
            'pos_shift_id' => $shift['id'] ?? null,
            'tax_template_id' => $profile['tax_template_id'] ?? null,
            'payment_method' => $paymentMethod,
            'status' => $validated['order_type'] === 'pos' ? 'completed' : 'draft',
            'subtotal' => $data['subtotal'],
            'discount_total' => $data['discount_total'],
            'shipping_fee' => $shippingFee,
            'coupon_code' => $coupon['code'] ?? null,
            'coupon_discount' => $couponDiscount,
            'loyalty_points_redeemed' => $redeemedPoints,
            'loyalty_discount' => $loyaltyDiscount,
            'tax_total' => $taxResult['tax_total'],
            'rounding_adjustment' => $taxResult['rounding_adjustment'],
            'total' => $totalWithShipping,
            'paid_amount' => $initialPaid,
            'debt_amount' => $initialDebt,
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

        $order = [];
        $this->db->transBegin();
        try {
            $order = $this->orders->create($orderPayload, $itemRows, false);
            $order['items'] = $itemRows;

            if (! empty($payments)) {
                foreach ($payments as $payment) {
                    $this->paymentService->addPayment([
                        'order_id' => $order['id'],
                        'payment_method' => $payment['payment_method'],
                        'amount' => $payment['amount'],
                        'created_by' => $validated['created_by'] ?? $validated['user_id'] ?? 1,
                    ]);
                    $this->paymentEntries->create([
                        'order_id' => $order['id'],
                        'payment_method' => $payment['payment_method'],
                        'amount' => $payment['amount'],
                        'reference' => $payment['reference'] ?? null,
                    ]);
                    if ($shift) {
                        $this->shiftService->recordPayment($shift['id'], [
                            'order_id' => $order['id'],
                            'payment_method' => $payment['payment_method'],
                            'amount' => $payment['amount'],
                        ]);
                    }
                }
                $orderRefreshed = $this->orders->findById($order['id']);
                if ($orderRefreshed) {
                    $order = array_merge($order, array_filter($orderRefreshed, static fn ($v) => $v !== null));
                }
                $isPaidFinal = ((float) ($order['debt_amount'] ?? 0)) <= 0.0001;
                $this->orders->updateFields($order['id'], [
                    'is_paid' => $isPaidFinal ? 1 : 0,
                    'pos_shift_id' => $shift['id'] ?? null,
                ]);
                $order['is_paid'] = $isPaidFinal ? 1 : 0;
                if ($shift) {
                    $order['pos_shift_id'] = $shift['id'];
                }
            }

            $earnedPoints = 0;
            if (! empty($validated['customer_id'])) {
                if ($redeemedPoints > 0) {
                    $this->loyaltyService->redeem((int) $validated['customer_id'], $redeemedPoints, $order['id']);
                }
                $earnedPoints = $this->loyaltyService->earn((int) $validated['customer_id'], $totalWithShipping, $order['id']);
                if ($earnedPoints > 0) {
                    $this->orders->updateFields($order['id'], ['loyalty_points_earned' => $earnedPoints]);
                    $order['loyalty_points_earned'] = $earnedPoints;
                }
            }
            if ($coupon) {
                $this->couponService->markUsed($coupon, $order['id'], $validated['customer_id'] ?? null);
            }

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
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
        $warehouseId = isset($order['warehouse_id']) ? (int) $order['warehouse_id'] : $branchId;
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
                    'warehouse_id' => $warehouseId,
                    'serial_number' => $serials ? implode(',', $serials) : null,
                    'movement_type' => 'sale',
                ]);
            } else {
                $this->inventoryRepo->adjustStockWithLock($productId, $variantId ? (int) $variantId : null, $warehouseId, -$qty, $branchId);
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

        $db = $this->db;
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

        $db = $this->db;
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
        $db = $this->db;
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
