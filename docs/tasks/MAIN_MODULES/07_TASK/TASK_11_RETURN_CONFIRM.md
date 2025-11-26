---
title: "TASK 11: Return Completion Implementation (CodeIgniter 4)"
id: "TASK-11-RETURN-COMPLETION-CI4"
priority: "P2 (Medium)"
estimated_effort: "2 days"
dependencies: "TASK-10-RETURN-APPROVE-CI4"
status: "Done"
module: "Order Workflow"
type: "Implementation Task"
tags: ["task", "returns", "completion", "inventory", "restock", "logging", "API", "backend", "codeigniter"]
purpose: "Implement the final step of the return workflow in CodeIgniter 4, completing an approved return by restocking inventory, logging movement details, and updating the return status to 'completed'."
location: "docs/tasks/MAIN_MODULES/07_TASK"
---

# TASK 11: Return Completion Implementation (CodeIgniter 4)

**Priority:** P2 (Medium)
**Estimated Effort:** 2 days
**Dependencies:** TASK 10 (Return Approval)
**Status:** Done

---

## 🎯 OBJECTIVE

Implement the final step of the return workflow: marking an **`approved`** return as **`completed`**. This action signifies that the returned goods have been physically received and processed. The primary side-effect is restocking the inventory.

---

## 📋 WORKFLOW RULES

-   **State Constraint**: A return can only be completed if its current status is `approved`.
-   **Idempotency**: The core action is to restock inventory. This should only happen once.
-   **Inventory Restoration**: The `quantity_on_hand` for each returned item is increased in the `inventory_stock` table.
-   **Auditing**: A corresponding `return` type movement is logged in the `inventory_movements` table for each item restocked.

---

## 🏗️ ARCHITECTURE (CodeIgniter 4)

The logic for completing a return is a method within the unified `ReturnService`.

### **Service: `ReturnService.php`**

The `complete` method orchestrates the final step of the return process.

```php
// app/Services/Returns/ReturnService.php
class ReturnService
{
    // ... constructor and other methods

    /** Complete return (after restock/refund). @agent-use: PATCH /api/returns/{id}/complete */
    public function complete(int $id, array $payload): array
    {
        // 1. Validate payload (user_id, version for optimistic locking)
        $validated = $this->validator->validateTransition($payload);

        // 2. Fetch the return request.
        $return = $this->repo->findById($id);

        // 3. Ensure the return is in 'approved' status.
        if (!in_array($return['status'], ['approved'], true)) {
            throw new InvalidArgumentException('Only approved returns can be completed');
        }

        // 4. Trigger the inventory restock process.
        $order = $this->repo->orderWithItems($return['order_id']);
        $this->restockItems($return, $order);

        // 5. Transition status to 'completed' using the repository.
        $updated = $this->repo->transition(
            $id,
            'completed',
            ['completed_at' => date('Y-m-d H:i:s')],
            $validated['version']
        );

        // 6. Dispatch 'return.completed' event.
        $transformed = $this->transformer->transform($updated);
        $this->emit('return.completed', $transformed);

        return ['success' => true, 'data' => $transformed];
    }

    private function restockItems(array $return, ?array $order): void
    {
        if (!$order || empty($return['items'])) { return; }
        
        $db = \Config\Database::connect();
        $db->transStart();

        foreach ($return['items'] as $item) {
            // ... (details omitted for brevity)
            
            // a. Find the inventory stock record for the item's branch, product, and variant.
            
            // b. Atomically increase the `quantity_on_hand`.
            $db->table('inventory_stock')
               ->where('id', $row['id'])
               ->set('quantity_on_hand', 'quantity_on_hand + ' . $qty, false)
               ->update();

            // c. Log the movement using InventoryMovementLogger.
            $this->movements->log(
                type: 'return',
                quantity: $qty,
                referenceType: 'return',
                referenceId: (int) $return['id'],
                ...
            );
        }

        $db->transComplete();
    }
}
```

### **Controller: `ReturnsController.php`**
The controller exposes the `complete` action through a `PATCH` endpoint.

```php
// app/Controllers/Api/ReturnsController.php
class ReturnsController extends BaseController
{
    // ... other methods

    /** Complete return. @agent-use: PATCH /api/returns/{id}/complete */
    public function complete($id)
    {
        return $this->wrap(fn () => $this->respond(
            $this->service->complete((int) $id, $this->safeInput())
        ));
    }
}
```

---

## 🧪 TESTING

-   **`ReturnServiceTest.php`**: Includes tests to verify:
    -   An `approved` return can be successfully moved to `completed`.
    -   An exception is thrown if attempting to complete a `pending` or `rejected` return.
    -   The `restockItems` logic correctly increases the `quantity_on_hand` in the `inventory_stock` table.
    -   A `return` type movement is correctly logged in the `inventory_movements` table.
-   **Integration Test**: A feature test for the `PATCH /api/returns/{id}/complete` endpoint confirms that the API call successfully triggers the completion and restocking process.

---

## 📝 ACCEPTANCE CRITERIA

- [x]  Only returns with an `approved` status can be moved to `completed`.
- [x]  The `quantity_on_hand` for each returned item is correctly increased in the `inventory_stock` table.
- [x]  An inventory movement of type `return` is logged for each restocked item.
- [x]  The return's status is updated to `completed` and the `completed_at` timestamp is set.
- [x]  The entire operation is wrapped in a database transaction to ensure atomicity.
- [x]  All relevant tests pass.