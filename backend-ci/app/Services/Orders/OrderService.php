<?php

namespace App\Services\Orders;

use App\Repositories\Orders\OrderRepository;
use App\Services\PriceLists\PriceCalculatorService;
use App\Validators\OrderValidator;
use Config\Database;
use RuntimeException;

/** Order creation + pricing. @agent-service: Orders @agent-pattern: Service orchestrator @agent-reusable: MEDIUM */
class OrderService
{
    protected OrderRepository $orders;
    protected OrderValidator $validator;
    protected PriceCalculatorService $pricing;

    public function __construct(
        ?OrderRepository $orders = null,
        ?OrderValidator $validator = null,
        ?PriceCalculatorService $pricing = null
    ) {
        $this->orders = $orders ?? new OrderRepository();
        $this->validator = $validator ?? new OrderValidator();
        $this->pricing = $pricing ?? new PriceCalculatorService();
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
            $calc = $this->pricing->getProductPrice($item['product_id'], $item['variant_id'], $groupId, (int) $item['quantity'], $validated['order_date']);
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
        $preview = $this->preview($payload);
        $data = $preview['data'];

        $orderPayload = [
            'customer_id' => $data['customer_id'],
            'customer_group_id' => $data['customer_group_id'],
            'order_date' => $data['order_date'],
            'status' => 'confirmed',
            'subtotal' => $data['subtotal'],
            'discount_total' => $data['discount_total'],
            'total' => $data['total'],
            'applied_price_list_id' => $data['applied_price_list_id'],
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
        return ['success' => true, 'data' => $order];
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
}
