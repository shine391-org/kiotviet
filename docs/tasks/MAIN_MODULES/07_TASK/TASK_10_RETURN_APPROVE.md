---
title: "TASK 10: Return Approval Implementation"
id: "TASK-10-RETURN-APPROVE-01"
priority: "P2 (Medium)"
estimated_effort: "3 days"
dependencies: "TASK_09"
status: "Blocked"
module: "Order Workflow"
type: "Implementation Task"
tags: ["task", "returns", "approval", "rejection", "workflow", "refund", "API", "backend"]
purpose: "Implement the administrative workflow for approving or rejecting return requests, including recalculating refund amounts based on shipping fee policy, storing refund methods, and updating return status."
location: "docs/tasks/MAIN_MODULES/07_TASK"
related_to:
  - id: "RETURN-RULES-01"
    description: "Defines validation for approval/rejection."
  - id: "RETURN-FLOW-01"
    description: "Describes the approval workflow."
  - id: "RETURNS-TABLES-01"
    description: "Schema for returns to be updated."
  - id: "FINANCIAL-EDGE-CASES-01"
    description: "Refund calculation edge cases."
  - id: "TASK-09-RETURN-REQUEST-01"
    description: "Dependency: Return request creation."
  - id: "ORDER-WORKFLOW-INDEX"
    description: "Task listed in the module index."
---

# TASK_10: Return Approval Implementation

**Priority:** P2 (Medium)

**Estimated Effort:** 3 days

**Dependencies:** TASK_09

**Status:** Blocked

---

## 🎯 OBJECTIVE

Implement **admin return approval/rejection** workflow with shipping fee refund logic.

---

## 📋 APPROVAL RULES

### **Who Can Approve**

- ✅ Admin users only
- ✅ Cannot be customer

### **Approval Logic**

- ✅ Can only approve/reject pending returns
- ✅ Set refund_shipping_fee flag
- ✅ Recalculate refund_amount if shipping included
- ✅ Set refund_method (cash or bank_transfer)
- ✅ Cannot edit after approval/rejection

### **Shipping Fee Refund Policy**

- ✅ `defective` → YES (shop's fault)
- ✅ `wrong_item` → YES (shop's fault)
- ✅ `not_satisfied` → NO (customer's fault)
- ✅ `other` → Admin decides

See [**RETURN_](https://www.notion.so/RETURN_RULES-Return-Validation-Rules-db33c3b127d8446890582464f443d07c?pvs=21)[RULES.md](http://RULES.md)**

---

## 🏗️ SERVICE IMPLEMENTATION

### **ReturnApprovalService**

```php
<?php

namespace App\Services;

use App\Models\Return;
use Illuminate\Support\Facades\DB;
use App\Exceptions\ValidationException;

class ReturnApprovalService
{
    public function approve(int $returnId, array $data): Return
    {
        return DB::transaction(function () use ($returnId, $data) {
            // Lock return
            $return = Return::where('id', $returnId)
                ->with('order')
                ->lockForUpdate()
                ->firstOrFail();
            
            // Validate
            $this->validateCanApprove($return);
            
            // Recalculate refund amount with shipping
            $refundAmount = $return->return_amount;
            if ($data['refund_shipping_fee']) {
                $refundAmount += $return->order->shipping_fee;
            }
            
            // Update return
            $return->update([
                'status' => 'approved',
                'refund_shipping_fee' => $data['refund_shipping_fee'],
                'refund_amount' => $refundAmount,
                'refund_method' => $data['refund_method'],
                'notes' => $data['notes'] ?? null,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            
            return $return->fresh(['returnItems.orderItem']);
        });
    }

    public function reject(int $returnId, string $reason): Return
    {
        return DB::transaction(function () use ($returnId, $reason) {
            $return = Return::where('id', $returnId)
                ->lockForUpdate()
                ->firstOrFail();
            
            // Validate
            $this->validateCanApprove($return);
            
            // Update return
            $return->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            
            return $return;
        });
    }

    private function validateCanApprove(Return $return): void
    {
        if ($return->status !== 'pending') {
            throw new ValidationException(
                "Can only approve/reject pending returns. Current status: {$return->status}",
                'RET_NOT_PENDING'
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
use App\Services\ReturnApprovalService;
use Illuminate\Http\Request;

class ReturnApprovalController extends Controller
{
    public function __construct(
        private ReturnApprovalService $approvalService
    ) {}

    /**
     * Approve a return request
     */
    public function approve(Request $request, int $id)
    {
        // Only admin can approve
        $this->authorize('approve', Return::class);
        
        $validated = $request->validate([
            'refund_shipping_fee' => 'required|boolean',
            'refund_method' => 'required|string|in:cash,bank_transfer',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $return = $this->approvalService->approve($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Return approved successfully',
                'data' => [
                    'id' => $return->id,
                    'return_number' => $return->return_number,
                    'status' => $return->status,
                    'return_amount' => $return->return_amount,
                    'refund_shipping_fee' => $return->refund_shipping_fee,
                    'refund_amount' => $return->refund_amount,
                    'refund_method' => $return->refund_method,
                    'approved_by' => [
                        'id' => $return->approvedBy->id,
                        'name' => $return->approvedBy->name,
                    ],
                    'approved_at' => $return->approved_at->toIso8601String(),
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

    /**
     * Reject a return request
     */
    public function reject(Request $request, int $id)
    {
        // Only admin can reject
        $this->authorize('reject', Return::class);
        
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $return = $this->approvalService->reject($id, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Return rejected',
                'data' => [
                    'id' => $return->id,
                    'return_number' => $return->return_number,
                    'status' => $return->status,
                    'rejection_reason' => $return->rejection_reason,
                    'approved_by' => [
                        'id' => $return->approvedBy->id,
                        'name' => $return->approvedBy->name,
                    ],
                    'approved_at' => $return->approved_at->toIso8601String(),
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

## 🛣️ ROUTES

```php
// routes/api.php

Route::prefix('returns')->middleware(['auth:api', 'admin'])->group(function () {
    Route::patch('/{id}/approve', [ReturnApprovalController::class, 'approve']);
    Route::patch('/{id}/reject', [ReturnApprovalController::class, 'reject']);
});
```

---

## 🔐 AUTHORIZATION POLICY

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Return;

class ReturnPolicy
{
    public function approve(User $user): bool
    {
        return $user->is_admin;
    }

    public function reject(User $user): bool
    {
        return $user->is_admin;
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
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReturnApprovalTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_approve_return()
    {
        $admin = User::factory()->admin()->create();
        $return = Return::factory()->create([
            'status' => 'pending',
            'return_amount' => 100000
        ]);
        $return->order->update(['shipping_fee' => 30000]);

        $response = $this->actingAs($admin, 'api')
            ->patchJson("/api/returns/{$return->id}/approve", [
                'refund_shipping_fee' => true,
                'refund_method' => 'bank_transfer',
                'notes' => 'Approved - valid defect'
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('returns', [
            'id' => $return->id,
            'status' => 'approved',
            'refund_amount' => 130000, // 100000 + 30000 shipping
            'refund_method' => 'bank_transfer',
            'approved_by' => $admin->id
        ]);
    }

    /** @test */
    public function non_admin_cannot_approve()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $return = Return::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($user, 'api')
            ->patchJson("/api/returns/{$return->id}/approve", [
                'refund_shipping_fee' => false,
                'refund_method' => 'cash'
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function cannot_approve_already_approved_return()
    {
        $admin = User::factory()->admin()->create();
        $return = Return::factory()->create(['status' => 'approved']);

        $response = $this->actingAs($admin, 'api')
            ->patchJson("/api/returns/{$return->id}/approve", [
                'refund_shipping_fee' => false,
                'refund_method' => 'cash'
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'error_code' => 'RET_NOT_PENDING'
            ]);
    }

    /** @test */
    public function admin_can_reject_return()
    {
        $admin = User::factory()->admin()->create();
        $return = Return::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($admin, 'api')
            ->patchJson("/api/returns/{$return->id}/reject", [
                'reason' => 'Items not eligible for return'
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('returns', [
            'id' => $return->id,
            'status' => 'rejected',
            'rejection_reason' => 'Items not eligible for return',
            'approved_by' => $admin->id
        ]);
    }
}
```

---

## 📝 ACCEPTANCE CRITERIA

- [x]  Admin can approve pending returns
- [x]  Admin can reject pending returns
- [x]  Non-admin cannot approve/reject
- [x]  Cannot approve/reject non-pending returns
- [x]  Refund amount recalculated with shipping
- [x]  Refund method stored
- [x]  Timestamps updated
- [x]  Tests passing

---

## 🔗 RELATED DOCUMENTS

- [**RETURN_](https://www.notion.so/RETURN_RULES-Return-Validation-Rules-db33c3b127d8446890582464f443d07c?pvs=21)[RULES.md](http://RULES.md)**
- [**RETURN_](https://www.notion.so/RETURN_FLOW-Return-Orders-Workflow-7f5c5a4af4054c22a265d011c56b10e3?pvs=21)[FLOW.md](http://FLOW.md)**
- [**FINANCIAL.md**](http://FINANCIAL.md)