# TASK_03_RETURNS_SCHEMA - Returns Schema Implementation

# TASK_03: Returns Schema Implementation

**Priority:** P1 (High)

**Estimated Effort:** 3 days

**Dependencies:** TASK_05 (Order Create)

**Status:** Blocked

---

## 🎯 OBJECTIVE

Implement **returns** and **return_items** tables with return workflow.

---

## 📋 REQUIREMENTS

### **Functional Requirements**

- [x]  Create returns table
- [x]  Create return_items table
- [x]  Generate return number automatically
- [x]  Support partial returns
- [x]  Implement approval workflow
- [x]  Calculate refund amount
- [x]  Track item condition

### **Non-Functional Requirements**

- [x]  Return number must be unique
- [x]  Support optimistic locking
- [x]  Transaction integrity

---

## 🗄️ DATABASE SCHEMA

See [**RETURNS_](https://www.notion.so/RETURNS_TABLES-Returns-Schema-4a31069a3d7f44999f9b01f61ed53b1a?pvs=21)[TABLES.md](http://TABLES.md)** for full schema.

### **Key Points**

```sql
CREATE TABLE returns (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    return_number VARCHAR(50) UNIQUE NOT NULL, -- TH-{order_id}-{counter}
    order_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    
    -- Financial
    return_amount DECIMAL(15,2) NOT NULL,
    refund_shipping_fee BOOLEAN DEFAULT FALSE,
    refund_amount DECIMAL(15,2) NOT NULL,
    refund_method ENUM('cash', 'bank_transfer') NULL,
    
    -- Status workflow
    status ENUM('pending', 'approved', 'rejected', 'completed') NOT NULL DEFAULT 'pending',
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
    INDEX idx_status (status)
);

CREATE TABLE return_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    return_id BIGINT UNSIGNED NOT NULL,
    order_item_id BIGINT UNSIGNED NOT NULL,
    quantity_returned INT NOT NULL,
    condition ENUM('new', 'used', 'damaged') NULL,
    
    FOREIGN KEY (return_id) REFERENCES returns(id) ON DELETE CASCADE
);
```

---

## 🔢 RETURN NUMBER GENERATION

```php
<?php

namespace App\Services;

use App\Models\Return;
use Illuminate\Support\Facades\DB;

class ReturnNumberGenerator
{
    public function generate(int $orderId): string
    {
        return DB::transaction(function () use ($orderId) {
            $counter = Return::where('order_id', $orderId)
                ->lockForUpdate()
                ->count() + 1;
            
            return sprintf("TH-%d-%d", $orderId, $counter);
        });
    }
}
```

---

## 💰 REFUND CALCULATION

```php
<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Return;

class RefundCalculator
{
    public function calculate(Order $order, array $returnItems, bool $refundShipping = false): array
    {
        $returnAmount = 0;
        
        foreach ($returnItems as $item) {
            $orderItem = $order->items()->find($item['order_item_id']);
            $returnAmount += $orderItem->price * $item['quantity_returned'];
        }
        
        $refundAmount = $returnAmount;
        
        // Add shipping fee if applicable
        if ($refundShipping) {
            $refundAmount += $order->shipping_fee;
        }
        
        return [
            'return_amount' => $returnAmount,
            'refund_amount' => $refundAmount,
        ];
    }
}
```

---

## ✅ VALIDATION RULES

See [**RETURN_](https://www.notion.so/RETURN_RULES-Return-Validation-Rules-db33c3b127d8446890582464f443d07c?pvs=21)[RULES.md](http://RULES.md)**

**Key Validations:**

- Order must be completed
- Within return window (30 days)
- Quantity not exceeding purchased quantity
- Cannot over-return

---

## 🧪 TESTING

### **Unit Test: Return Number Generation**

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ReturnNumberGenerator;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReturnNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_sequential_return_numbers()
    {
        $order = Order::factory()->create();
        $generator = new ReturnNumberGenerator();
        
        $num1 = $generator->generate($order->id);
        $num2 = $generator->generate($order->id);
        
        $this->assertEquals("TH-{$order->id}-1", $num1);
        $this->assertEquals("TH-{$order->id}-2", $num2);
    }
}
```

---

### **Feature Test: Create Return**

```php
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReturnApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_return_request()
    {
        $order = Order::factory()->create([
            'status' => 'completed',
            'completed_at' => now()->subDays(5)
        ]);
        
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 3,
            'price' => 100000
        ]);
        
        $response = $this->postJson('/api/returns', [
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
        $order = Order::factory()->create(['status' => 'completed']);
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 2
        ]);
        
        $response = $this->postJson('/api/returns', [
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
}
```

---

## 📝 ACCEPTANCE CRITERIA

- [x]  Returns table created
- [x]  Return_items table created
- [x]  Return number generation works
- [x]  Can create return with multiple items
- [x]  Cannot return more than purchased
- [x]  Refund calculation correct
- [x]  Approval workflow implemented
- [x]  All tests passing

---

## 🔗 RELATED DOCUMENTS

- [**RETURNS_](https://www.notion.so/RETURNS_TABLES-Returns-Schema-4a31069a3d7f44999f9b01f61ed53b1a?pvs=21)[TABLES.md](http://TABLES.md)** - Schema details
- [**RETURN_](https://www.notion.so/RETURN_RULES-Return-Validation-Rules-db33c3b127d8446890582464f443d07c?pvs=21)[RULES.md](http://RULES.md)** - Validation rules
- [**RETURN_](https://www.notion.so/RETURN_FLOW-Return-Orders-Workflow-7f5c5a4af4054c22a265d011c56b10e3?pvs=21)[FLOW.md](http://FLOW.md)** - Workflow