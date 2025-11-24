# TASK_11: Return Completion Implementation

**Priority:** P2 (Medium)

**Estimated Effort:** 4 days

**Dependencies:** TASK_10

**Status:** Blocked

---

## 🎯 OBJECTIVE

Implement **return completion** with inventory restocking and movement logging.

---

## 📋 COMPLETION RULES

- ✅ Can only complete approved returns
- ✅ Restock inventory to original branch
- ✅ Log all inventory movements
- ✅ Track item condition (new/used/damaged)
- ✅ Update completion timestamp

---

## 🏗️ SERVICE IMPLEMENTATION

```php
<?php

namespace App\Services;

use App\Models\Return;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;
use App\Exceptions\ValidationException;

class ReturnCompletionService
{
    public function __construct(
        private InventoryMovementLogger $movementLogger
    ) {}

    public function complete(int $returnId): Return
    {
        return DB::transaction(function () use ($returnId) {
            // Lock return
            $return = Return::where('id', $returnId)
                ->with(['returnItems.orderItem', 'order'])
                ->lockForUpdate()
                ->firstOrFail();
            
            // Validate
            $this->validateCanComplete($return);
            
            // Restock all items
            foreach ($return->returnItems as $item) {
                $this->restockItem($return, $item);
            }
            
            // Mark as completed
            $return->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            
            return $return->fresh();
        });
    }

    private function validateCanComplete(Return $return): void
    {
        if ($return->status !== 'approved') {
            throw new ValidationException(
                "Can only complete approved returns. Current status: {$return->status}",
                'RET_NOT_APPROVED'
            );
        }
    }

    private function restockItem(Return $return, ReturnItem $item): void
    {
        $orderItem = $item->orderItem;
        
        // Restock inventory
        $inventory = Inventory::where('branch_id', $return->order->branch_id)
            ->where('product_id', $orderItem->product_id)
            ->where('variant_id', $orderItem->variant_id)
            ->lockForUpdate()
            ->firstOrFail();
        
        $inventory->increment('quantity', $item->quantity_returned);
        
        // Log movement
        $this->movementLogger->log(
            branchId: $return->order->branch_id,
            productId: $orderItem->product_id,
            variantId: $orderItem->variant_id,
            type: 'return',
            quantity: +$item->quantity_returned,
            referenceType: 'return',
            referenceId: $return->id,
            notes: $this->buildMovementNotes($return, $item)
        );
    }

    private function buildMovementNotes(Return $return, ReturnItem $item): string
    {
        $notes = "Return #{$return->return_number}";
        
        if ($item->condition) {
            $notes .= " - Condition: {$item->condition}";
        }
        
        if ($return->reason) {
            $notes .= " - Reason: {$return->reason}";
        }
        
        return $notes;
    }
}
```

---

## 🌐 API CONTROLLER

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReturnCompletionService;
use Illuminate\Http\Request;

class ReturnCompletionController extends Controller
{
    public function __construct(
        private ReturnCompletionService $completionService
    ) {}

    public function complete(int $id)
    {
        // Only admin/warehouse staff can complete
        $this->authorize('complete', Return::class);

        try {
            $return = $this->completionService->complete($id);

            return response()->json([
                'success' => true,
                'message' => 'Return completed successfully',
                'data' => [
                    'id' => $return->id,
                    'return_number' => $return->return_number,
                    'status' => $return->status,
                    'completed_at' => $return->completed_at->toIso8601String(),
                    'items' => $return->returnItems->map(fn($item) => [
                        'product_name' => $item->orderItem->product_name,
                        'quantity_returned' => $item->quantity_returned,
                        'condition' => $item->condition,
                    ]),
                ]
            ]);
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
use App\Models\Return;
use App\Models\ReturnItem;
use App\Models\OrderItem;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReturnCompletionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_complete_approved_return()
    {
        $admin = User::factory()->admin()->create();
        $return = Return::factory()->create(['status' => 'approved']);
        
        $orderItem = OrderItem::factory()->create([
            'order_id' => $return->order_id,
            'product_id' => 101,
            'variant_id' => 201,
            'quantity' => 5
        ]);
        
        $returnItem = ReturnItem::factory()->create([
            'return_id' => $return->id,
            'order_item_id' => $orderItem->id,
            'quantity_returned' => 2,
            'condition' => 'new'
        ]);
        
        $inventory = Inventory::factory()->create([
            'branch_id' => $return->order->branch_id,
            'product_id' => 101,
            'variant_id' => 201,
            'quantity' => 10
        ]);

        $response = $this->actingAs($admin, 'api')
            ->patchJson("/api/returns/{$return->id}/complete");

        $response->assertStatus(200);

        // Check inventory increased
        $inventory->refresh();
        $this->assertEquals(12, $inventory->quantity); // 10 + 2

        // Check return status
        $this->assertDatabaseHas('returns', [
            'id' => $return->id,
            'status' => 'completed'
        ]);

        // Check movement logged
        $this->assertDatabaseHas('inventory_movements', [
            'branch_id' => $return->order->branch_id,
            'product_id' => 101,
            'variant_id' => 201,
            'type' => 'return',
            'quantity' => 2,
            'reference_type' => 'return',
            'reference_id' => $return->id
        ]);
    }

    /** @test */
    public function it_cannot_complete_pending_return()
    {
        $admin = User::factory()->admin()->create();
        $return = Return::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($admin, 'api')
            ->patchJson("/api/returns/{$return->id}/complete");

        $response->assertStatus(400)
            ->assertJson([
                'error_code' => 'RET_NOT_APPROVED'
            ]);
    }

    /** @test */
    public function it_cannot_complete_rejected_return()
    {
        $admin = User::factory()->admin()->create();
        $return = Return::factory()->create(['status' => 'rejected']);

        $response = $this->actingAs($admin, 'api')
            ->patchJson("/api/returns/{$return->id}/complete");

        $response->assertStatus(400);
    }
}
```

---

## 📝 ACCEPTANCE CRITERIA

- [x]  Can only complete approved returns
- [x]  Inventory restocked correctly
- [x]  Movements logged with details
- [x]  Item condition tracked
- [x]  Completion timestamp recorded
- [x]  Tests passing

---

## 🔗 RELATED DOCUMENTS

- [**RETURN_](https://www.notion.so/RETURN_FLOW-Return-Orders-Workflow-7f5c5a4af4054c22a265d011c56b10e3?pvs=21)[FLOW.md](http://FLOW.md)**
- [**INVENTORY.md**](http://INVENTORY.md)
- [**SUPPORTING_](https://www.notion.so/SUPPORTING_TABLES-Supporting-Tables-Schema-88f443621e234de7a42f404439a5ac57?pvs=21)[TABLES.md](http://TABLES.md)**