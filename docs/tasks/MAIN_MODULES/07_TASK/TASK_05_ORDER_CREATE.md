---
title: "TASK 05: Order Create Implementation"
id: "TASK-05-ORDER-CREATE-01"
priority: "P0 (Blocker)"
estimated_effort: "5 days"
dependencies: "TASK_01, TASK_04"
status: "Ready"
module: "Order Workflow"
type: "Implementation Task"
tags: ["task", "orders", "create", "POS", "SHIPPING", "validation", "inventory", "API", "backend"]
purpose: "Implement the full order creation logic, including order number generation, financial calculations, stock validation, and initial status management for both POS and Shipping order types."
location: "docs/tasks/MAIN_MODULES/07_TASK"
related_to:
  - id: "ORDERS-TABLE-01"
    description: "Schema implemented by this task."
  - id: "ORDER-RULES-01"
    description: "Validation rules implemented by this task."
  - id: "SHIPPING-FLOW-01"
    description: "Related workflow for Shipping orders."
  - id: "POS-FLOW-01"
    description: "Related workflow for POS orders."
  - id: "PAY-001"
    description: "Dependency: Payment Methods implementation (TASK_01)."
  - id: "TASK-04-SUPPORTING-TABLES-01"
    description: "Dependency: Supporting Tables implementation for logging and inventory (TASK_04)."
  - id: "API-PAYLOADS-EXAMPLES-01"
    description: "Provides API examples for this task."
  - id: "ORDER-WORKFLOW-INDEX"
    description: "Task listed in the module index."
---

# TASK_05: Order Create Implementation

**Priority:** P0 (Blocker)

**Estimated Effort:** 5 days

**Dependencies:** TASK_01, TASK_04

**Status:** Ready

---

## 🎯 OBJECTIVE

Implement **full order creation logic** with validation, inventory deduction, and status management.

---

## 📋 REQUIREMENTS

### **Functional Requirements**

- [x]  Create orders table schema
- [x]  Create order_items table schema
- [x]  Generate order number automatically
- [x]  Support 2 order types: POS & SHIPPING
- [x]  Calculate subtotal, discount, total
- [x]  Validate stock availability
- [x]  Deduct inventory on processing
- [x]  Support partial payment tracking

### **Non-Functional Requirements**

- [x]  Thread-safe order number generation
- [x]  Transaction integrity
- [x]  Response time < 500ms

---

## 🗄️ DATABASE SCHEMA

See [**ORDERS_](https://www.notion.so/ORDERS_TABLE-Orders-Table-Schema-36b32ddd5ce6421abd19d92589270881?pvs=21)[TABLE.md](http://TABLE.md)** for full schema.

---

## 🔢 ORDER NUMBER GENERATION

### **Service Class**

```php
<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderNumberGenerator
{
    public function generate(): string
    {
        return DB::transaction(function () {
            $counter = Order::lockForUpdate()->count() + 1;
            return sprintf("ORD-%06d", $counter);
        });
    }
}
```

---

## 💰 ORDER CALCULATOR

### **Service Class**

```php
<?php

namespace App\Services;

class OrderCalculator
{
    public function calculate(array $items, float $discount, float $shippingFee): array
    {
        $subtotal = 0;
        
        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        $total = $subtotal - $discount + $shippingFee;
        
        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'shipping_fee' => round($shippingFee, 2),
            'total' => round($total, 2),
        ];
    }
}
```

---

## 🏗️ ORDER SERVICE

### **Create Order**

```php
<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private OrderNumberGenerator $numberGenerator,
        private OrderCalculator $calculator,
        private OrderValidator $validator
    ) {}

    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Validate
            $this->validator->validateCreate($data);
            
            // Generate order number
            $orderNumber = $this->numberGenerator->generate();
            
            // Calculate financials
            $calculated = $this->calculator->calculate(
                $data['items'],
                $data['discount'] ?? 0,
                $data['shipping_fee'] ?? 0
            );
            
            // Create order
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $data['customer_id'],
                'branch_id' => $data['branch_id'],
                'order_type' => $data['order_type'],
                'payment_method' => $data['payment_method'],
                'subtotal' => $calculated['subtotal'],
                'discount' => $calculated['discount'],
                'shipping_fee' => $calculated['shipping_fee'],
                'total' => $calculated['total'],
                'paid_amount' => $data['paid_amount'] ?? 0,
                'is_paid' => $this->determineIsPaid($data),
                'debt_amount' => $calculated['total'] - ($data['paid_amount'] ?? 0),
                'status' => $this->determineInitialStatus($data),
                // Shipping info
                'shipping_name' => $data['shipping']['name'] ?? null,
                'shipping_phone' => $data['shipping']['phone'] ?? null,
                'shipping_address' => $data['shipping']['address'] ?? null,
                'shipping_ward' => $data['shipping']['ward'] ?? null,
                'shipping_district' => $data['shipping']['district'] ?? null,
                'shipping_city' => $data['shipping']['city'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);
            
            // Create order items
            foreach ($data['items'] as $itemData) {
                $this->createOrderItem($order, $itemData);
            }
            
            // Load relationships
            $order->load(['items', 'customer', 'branch']);
            
            return $order;
        });
    }

    private function createOrderItem(Order $order, array $itemData): OrderItem
    {
        $product = Product::find($itemData['product_id']);
        $variant = isset($itemData['variant_id']) 
            ? Variant::find($itemData['variant_id']) 
            : null;
        
        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $itemData['product_id'],
            'variant_id' => $itemData['variant_id'] ?? null,
            'product_name' => $product->name,
            'variant_name' => $variant?->name,
            'sku' => $variant?->sku ?? $product->sku,
            'price' => $itemData['price'],
            'quantity' => $itemData['quantity'],
            'subtotal' => $itemData['price'] * $itemData['quantity'],
        ]);
    }

    private function determineIsPaid(array $data): bool
    {
        $total = $data['total'] ?? 0;
        $paidAmount = $data['paid_amount'] ?? 0;
        
        return abs($total - $paidAmount) < 0.01;
    }

    private function determineInitialStatus(array $data): string
    {
        // POS orders are completed immediately
        if ($data['order_type'] === 'POS') {
            return 'completed';
        }
        
        // Shipping orders start as draft
        return 'draft';
    }
}
```

---

## ✅ ORDER VALIDATOR

```php
<?php

namespace App\Services;

use App\Exceptions\ValidationException;

class OrderValidator
{
    public function validateCreate(array $data): void
    {
        // Rule: Customer required
        if (empty($data['customer_id'])) {
            throw new ValidationException('Customer is required', 'ORD_CUSTOMER_REQUIRED');
        }
        
        // Rule: Branch required
        if (empty($data['branch_id'])) {
            throw new ValidationException('Branch is required', 'ORD_BRANCH_REQUIRED');
        }
        
        // Rule: At least one item
        if (empty($data['items']) || count($data['items']) === 0) {
            throw new ValidationException('Order must have at least one item', 'ORD_NO_ITEMS');
        }
        
        // Rule: POS orders must use CASH
        if ($data['order_type'] === 'POS' && $data['payment_method'] !== 'CASH') {
            throw new ValidationException('POS orders must use CASH payment', 'ORD_POS_MUST_CASH');
        }
        
        // Rule: POS orders must be fully paid
        if ($data['order_type'] === 'POS') {
            $total = $this->calculateTotal($data);
            $paidAmount = $data['paid_amount'] ?? 0;
            
            if (abs($total - $paidAmount) > 0.01) {
                throw new ValidationException('POS orders must be fully paid', 'ORD_POS_UNPAID');
            }
        }
        
        // Rule: Shipping info required for SHIPPING orders
        if ($data['order_type'] === 'SHIPPING') {
            if (empty($data['shipping']['address'])) {
                throw new ValidationException('Shipping address is required', 'ORD_SHIPPING_ADDRESS_REQUIRED');
            }
        }
        
        // Validate each item
        foreach ($data['items'] as $item) {
            $this->validateItem($item, $data['branch_id']);
        }
    }

    private function validateItem(array $item, int $branchId): void
    {
        // Rule: Product exists
        $product = Product::find($item['product_id']);
        if (!$product) {
            throw new ValidationException(
                "Product #{$item['product_id']} not found",
                'ORD_PRODUCT_NOT_FOUND'
            );
        }
        
        // Rule: Variant required if product has variants
        if ($product->has_variants && empty($item['variant_id'])) {
            throw new ValidationException(
                "Product #{$product->id} requires variant selection",
                'ORD_VARIANT_REQUIRED'
            );
        }
        
        // Rule: Stock available
        $this->validateStock($item, $branchId);
    }

    private function validateStock(array $item, int $branchId): void
    {
        $inventory = Inventory::where('branch_id', $branchId)
            ->where('product_id', $item['product_id'])
            ->where('variant_id', $item['variant_id'] ?? null)
            ->first();
        
        if (!$inventory || $inventory->quantity < $item['quantity']) {
            throw new ValidationException(
                "Insufficient stock for product #{$item['product_id']}",
                'ORD_INSUFFICIENT_STOCK'
            );
        }
    }
}
```

---

## 🌐 API CONTROLLER

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    public function store(Request $request)
    {
        try {
            $order = $this->orderService->create($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order
            ], 201);
        } catch (\App\Exceptions\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ], 400);
        }
    }
}
```

---

## 🧪 TESTING

See [**API_](https://www.notion.so/API_PAYLOADS-API-Payloads-Examples-52a881d8585f477db57e4cddabdcc004?pvs=21)[PAYLOADS.md](http://PAYLOADS.md)** for examples.

---

## 📝 ACCEPTANCE CRITERIA

- [x]  Can create POS order
- [x]  Can create SHIPPING order
- [x]  Order number generated uniquely
- [x]  Items saved correctly
- [x]  Financial calculations accurate
- [x]  Stock validation works
- [x]  POS orders auto-completed
- [x]  All validation rules enforced
- [x]  All tests passing

---

## 🔗 RELATED DOCUMENTS

- [**ORDERS_](https://www.notion.so/ORDERS_TABLE-Orders-Table-Schema-36b32ddd5ce6421abd19d92589270881?pvs=21)[TABLE.md](http://TABLE.md)** - Schema
- [**ORDER_](https://www.notion.so/ORDER_RULES-Order-Validation-Rules-2b69907faaac48bdba1d2159793193c3?pvs=21)[RULES.md](http://RULES.md)** - Validation rules
- [**ORDER_](https://www.notion.so/SHIPPING_FLOW-SHIPPING-Orders-Workflow-c9c2807fa20e46d99712079779edf072?pvs=21)[FLOW.md](http://FLOW.md)** - Workflow