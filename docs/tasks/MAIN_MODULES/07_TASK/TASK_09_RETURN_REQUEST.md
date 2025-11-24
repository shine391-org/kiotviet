# TASK_09: Return Request Implementation

**Priority:** P2 (Medium)

**Estimated Effort:** 4 days

**Dependencies:** TASK_05

**Status:** Blocked

---

## 🎯 OBJECTIVE

Implement **customer return request** creation with full validation.

---

## 📋 VALIDATION RULES

### **Order Validation**

- ✅ Order must exist
- ✅ Order must be completed
- ✅ Within return window (30 days from completed_at)
- ✅ Customer must own the order

### **Items Validation**

- ✅ At least one item to return
- ✅ Items must belong to order
- ✅ Cannot return more than purchased
- ✅ Cannot over-return (considering previous returns)

See [**RETURN_](https://www.notion.so/RETURN_RULES-Return-Validation-Rules-db33c3b127d8446890582464f443d07c?pvs=21)[RULES.md](http://RULES.md)**

---

## 🏗️ SERVICE IMPLEMENTATION

### **ReturnRequestService**

```php
<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Return;
use App\Models\ReturnItem;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use App\Exceptions\ValidationException;

class ReturnRequestService
{
    private const RETURN_WINDOW_DAYS = 30;

    public function __construct(
        private ReturnNumberGenerator $numberGenerator,
        private RefundCalculator $refundCalculator
    ) {}

    public function create(array $data): Return
    {
        return DB::transaction(function () use ($data) {
            // Lock order
            $order = Order::where('id', $data['order_id'])
                ->lockForUpdate()
                ->firstOrFail();
            
            // Validate
            $this->validateOrder($order);
            $this->validateItems($order, $data['items']);
            
            // Generate return number
            $returnNumber = $this->numberGenerator->generate($order->id);
            
            // Calculate refund
            $refund = $this->refundCalculator->calculate(
                $order,
                $data['items'],
                false // refund_shipping_fee will be decided on approval
            );
            
            // Create return
            $return = Return::create([
                'return_number' => $returnNumber,
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'reason' => $data['reason'],
                'reason_detail' => $data['reason_detail'] ?? null,
                'return_amount' => $refund['return_amount'],
                'refund_amount' => $refund['refund_amount'],
                'refund_shipping_fee' => false,
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);
            
            // Create return items
            foreach ($data['items'] as $itemData) {
                ReturnItem::create([
                    'return_id' => $return->id,
                    'order_item_id' => $itemData['order_item_id'],
                    'quantity_returned' => $itemData['quantity_returned'],
                    'condition' => $itemData['condition'] ?? null,
                ]);
            }
            
            return $return->load(['returnItems.orderItem', 'order']);
        });
    }

    private function validateOrder(Order $order): void
    {
        // Must be completed
        if ($order->status !== 'completed') {
            throw new ValidationException(
                'Can only return completed orders',
                'RET_ORDER_NOT_COMPLETED'
            );
        }
        
        // Within return window
        if (!$order->completed_at) {
            throw new ValidationException(
                'Order has no completion date',
                'RET_NO_COMPLETION_DATE'
            );
        }
        
        $daysSinceCompleted = now()->diffInDays($order->completed_at);
        if ($daysSinceCompleted > self::RETURN_WINDOW_DAYS) {
            throw new ValidationException(
                "Return window expired. Orders can only be returned within {$daysSinceCompleted} days",
                'RET_WINDOW_EXPIRED'
            );
        }
        
        // Customer owns order
        if ($order->customer_id !== auth()->user()->customer_id) {
            throw new ValidationException(
                'You can only return your own orders',
                'RET_NOT_YOUR_ORDER'
            );
        }
    }

    private function validateItems(Order $order, array $items): void
    {
        if (empty($items)) {
            throw new ValidationException(
                'Must specify at least one item to return',
                'RET_NO_ITEMS'
            );
        }
        
        foreach ($items as $item) {
            $this->validateItem($order, $item);
        }
    }

    private function validateItem(Order $order, array $item): void
    {
        // Item belongs to order
        $orderItem = OrderItem::where('id', $item['order_item_id'])
            ->where('order_id', $order->id)
            ->first();
        
        if (!$orderItem) {
            throw new ValidationException(
                "Item #{$item['order_item_id']} does not belong to this order",
                'RET_ITEM_NOT_IN_ORDER'
            );
        }
        
        // Calculate already returned quantity
        $alreadyReturned = ReturnItem::whereHas('return', function ($q) use ($order) {
                $q->where('order_id', $order->id)
                  ->whereIn('status', ['pending', 'approved', 'completed']);
            })
            ->where('order_item_id', $orderItem->id)
            ->sum('quantity_returned');
        
        $remainingQuantity = $orderItem->quantity - $alreadyReturned;
        
        // Cannot return more than remaining
        if ($item['quantity_returned'] > $remainingQuantity) {
            throw new ValidationException(
                "Cannot return {$item['quantity_returned']} units. Only {$remainingQuantity} remaining",
                'RET_QUANTITY_EXCEEDED'
            );
        }
        
        // Quantity must be positive
        if ($item['quantity_returned'] <= 0) {
            throw new ValidationException(
                'Return quantity must be greater than 0',
                'RET_INVALID_QUANTITY'
            );
        }
    }
}
```

---

## 💰 REFUND CALCULATOR

```php
<?php

namespace App\Services;

use App\Models\Order;

class RefundCalculator
{
    public function calculate(
        Order $order,
        array $returnItems,
        bool $refundShipping = false
    ): array {
        $returnAmount = 0;
        
        foreach ($returnItems as $item) {
            $orderItem = $order->items()->find($item['order_item_id']);
            $returnAmount += $orderItem->price * $item['quantity_returned'];
        }
        
        $refundAmount = $returnAmount;
        
        // Optionally add shipping fee
        if ($refundShipping) {
            $refundAmount += $order->shipping_fee;
        }
        
        return [
            'return_amount' => round($returnAmount, 2),
            'refund_amount' => round($refundAmount, 2),
        ];
    }
}
```

---

## 🌐 API CONTROLLER

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReturnRequestService;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function __construct(
        private ReturnRequestService $returnService
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'reason' => 'required|string|in:defective,wrong_item,not_satisfied,other',
            'reason_detail' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|integer',
            'items.*.quantity_returned' => 'required|integer|min:1',
            'items.*.condition' => 'nullable|string|in:new,used,damaged',
        ]);

        try {
            $return = $this->returnService->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Return request created successfully',
                'data' => [
                    'id' => $return->id,
                    'return_number' => $return->return_number,
                    'order' => [
                        'id' => $return->order->id,
                        'order_number' => $return->order->order_number
                    ],
                    'items' => $return->returnItems->map(fn($item) => [
                        'order_item_id' => $item->order_item_id,
                        'product_name' => $item->orderItem->product_name,
                        'quantity_returned' => $item->quantity_returned,
                    ]),
                    'return_amount' => $return->return_amount,
                    'refund_amount' => $return->refund_amount,
                    'status' => $return->status,
                    'created_at' => $return->created_at->toIso8601String(),
                ]
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

```php
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReturnRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_return_request()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $user->customer_id,
            'status' => 'completed',
            'completed_at' => now()->subDays(5)
        ]);
        
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 3,
            'price' => 100000
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/returns', [
                'order_id' => $order->id,
                'reason' => 'defective',
                'reason_detail' => 'Product is broken',
                'items' => [
                    [
                        'order_item_id' => $item->id,
                        'quantity_returned' => 1
                    ]
                ]
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'return_number',
                    'status',
                    'return_amount'
                ]
            ]);

        $this->assertDatabaseHas('returns', [
            'order_id' => $order->id,
            'status' => 'pending',
            'return_amount' => 100000
        ]);
    }

    /** @test */
    public function it_cannot_return_more_than_purchased()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $user->customer_id,
            'status' => 'completed',
            'completed_at' => now()
        ]);
        
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 2
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/returns', [
                'order_id' => $order->id,
                'reason' => 'defective',
                'items' => [
                    [
                        'order_item_id' => $item->id,
                        'quantity_returned' => 5
                    ]
                ]
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'RET_QUANTITY_EXCEEDED'
            ]);
    }

    /** @test */
    public function it_cannot_return_incomplete_order()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $user->customer_id,
            'status' => 'processing'
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/returns', [
                'order_id' => $order->id,
                'reason' => 'defective',
                'items' => []
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'RET_ORDER_NOT_COMPLETED'
            ]);
    }
}
```

---

## 📝 ACCEPTANCE CRITERIA

- [x]  Return number generated (TH-{order_id}-{counter})
- [x]  Order must be completed
- [x]  Within 30-day return window
- [x]  Cannot return more than purchased
- [x]  Cannot over-return (multiple returns)
- [x]  Refund amount calculated
- [x]  All validations working
- [x]  Tests passing

---

## 🔗 RELATED DOCUMENTS

- [**RETURN_](https://www.notion.so/RETURN_RULES-Return-Validation-Rules-db33c3b127d8446890582464f443d07c?pvs=21)[RULES.md](http://RULES.md)**
- [**RETURN_](https://www.notion.so/RETURN_FLOW-Return-Orders-Workflow-7f5c5a4af4054c22a265d011c56b10e3?pvs=21)[FLOW.md](http://FLOW.md)**
- [**RETURNS_](https://www.notion.so/RETURNS_TABLES-Returns-Schema-4a31069a3d7f44999f9b01f61ed53b1a?pvs=21)[TABLES.md](http://TABLES.md)**