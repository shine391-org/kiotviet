---
title: "TASK 06: Order Status Management (CodeIgniter 4)"
id: "TASK-06-STATUS-MANAGEMENT-CI4"
priority: "P0 (Blocker)"
estimated_effort: "3 days"
dependencies: "TASK-05-ORDER-CREATE-CI4"
status: "Done"
module: "Order Workflow"
type: "Implementation Task"
tags: ["task", "order-status", "workflow", "state-machine", "inventory", "API", "backend", "codeigniter"]
purpose: "Implement the complete order status workflow in CodeIgniter 4, including valid transitions, inventory side effects (deduction/restoration), timestamp updates, and API endpoints."
location: "docs/tasks/MAIN_MODULES/07_TASK"
---

# TASK 06: Order Status Management (CodeIgniter 4)

**Priority:** P0 (Blocker)
**Estimated Effort:** 3 days
**Dependencies:** TASK 05 (Order Create)
**Status:** Done

---

## 🎯 OBJECTIVE

Implement the complete order status workflow using a state machine pattern in **CodeIgniter 4**. This includes validating transitions, handling inventory side-effects (deduction/restoration), and providing a secure API for status updates.

---

## 📋 STATUS WORKFLOW

The system defines a clear state machine for handling order statuses, primarily for `shipping` orders.

```
SHIPPING Orders Workflow:
draft → confirmed → processing → shipping → delivered → completed
      ↓           ↓            ↓          ↓
  cancelled   cancelled    cancelled  cancelled
```

-   **POS Orders**: These are considered complete upon creation and do not follow this workflow.
-   **Side Effects**: Key transitions trigger other processes, like inventory adjustments.

---

## 🏗️ ARCHITECTURE (CodeIgniter 4)

### **State Machine: `OrderStatusTransition.php`**

A dedicated class defines the valid state transitions, acting as a state machine. This centralizes the workflow logic and prevents invalid status changes.

```php
// app/Services/Orders/OrderStatusTransition.php
class OrderStatusTransition
{
    private const MAP = [
        'draft' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['shipping', 'cancelled'],
        'shipping' => ['delivered', 'cancelled'],
        'delivered' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function isValid(string $from, string $to): bool
    {
        return in_array($to, self::MAP[$from] ?? [], true);
    }
}
```

### **Core Logic: `OrderStatusService.php`**

This service is the heart of the status management system. It orchestrates validation, side effects, and persistence.

```php
// app/Services/Orders/OrderStatusService.php
class OrderStatusService
{
    public function updateStatus(int $orderId, string $toStatus, ...): array
    {
        // 1. Fetch the order
        $order = $this->orders->findById($orderId);
        
        // 2. Validate the transition using OrderStatusTransition
        if (! $this->transition->isValid($fromStatus, $toStatus)) {
            throw new InvalidArgumentException(...);
        }

        // 3. Apply side-effects (e.g., inventory changes)
        $this->applySideEffects($order, $fromStatus, $toStatus, $userId);

        // 4. Update the order's status and relevant timestamps
        $this->orders->updateFields($orderId, $updates);

        // 5. Log the status change for auditing
        $this->logs->create($orderId, $fromStatus, $toStatus, ...);

        // 6. Emit webhook event (e.g., 'order.confirmed')
        $this->emitStatus($toStatus, $updated);

        return ['success' => true, 'data' => $updated];
    }
}
```

### **Inventory Side-Effects**

The `OrderStatusService` is responsible for triggering inventory changes based on status transitions.

-   **`processing`**: When an order moves to `processing`, the inventory for each item is **deducted**.
-   **`cancelled`**: If an order is cancelled *after* the `processing` or `shipping` stage, the inventory is **restored**.

This logic is handled within the `applySideEffects` method, which calls a dedicated `InventoryMovementLogger` to ensure all stock changes are tracked.

```php
// app/Services/Orders/OrderStatusService.php -> applySideEffects()
private function applySideEffects(array $order, string $from, string $to, ?int $userId): void
{
    // Deduct inventory when entering processing
    if ($to === 'processing' && $from !== 'processing') {
        $this->deductInventory($order, $userId);
    }

    // Restore inventory when cancelling after deduction
    if ($to === 'cancelled' && in_array($from, ['processing', 'shipping'], true)) {
        $this->restoreInventory($order, $userId);
    }
}
```

### **Controller: `OrderStatusController.php`**

A thin controller provides the API endpoint for updating the status.

```php
// app/Controllers/Api/OrderStatusController.php
class OrderStatusController extends BaseController
{
    use ResponseTrait;
    protected OrderStatusService $service;

    public function __construct() { $this->service = service('orderStatusService'); }

    /** @agent-use: PATCH /api/orders/{id}/status */
    public function update($id)
    {
        $status = $this->request->getJSON(true)['status'] ?? null;
        return $this->wrap(fn () => $this->respond(
            $this->service->updateStatus((int) $id, $status, ...)
        ));
    }
}
```

---

## 🧪 TESTING

-   **`OrderStatusTransitionTest.php`**: Unit tests to ensure the state machine logic is correct (e.g., `draft` can go to `confirmed`, but not directly to `shipping`).
-   **`OrderStatusServiceTest.php`**: Verifies the core service logic, including:
    -   Correctly identifying valid/invalid transitions.
    -   Triggering inventory deduction when moving to `processing`.
    -   Triggering inventory restoration when a `processing` order is `cancelled`.
    -   Ensuring status logs are created.
-   **Integration Tests**: Test the `PATCH /api/orders/{id}/status` endpoint to confirm the entire workflow, including authentication, validation, and side-effects, works as expected.

---

## 📝 ACCEPTANCE CRITERIA

- [x] Status transitions are strictly enforced according to the defined state machine.
- [x] Inventory is correctly deducted when an order moves to `processing`.
- [x] Inventory is correctly restored if an order is cancelled from `processing` or `shipping` status.
- [x] No inventory changes occur for cancellations from `draft` or `confirmed`.
- [x] An entry is created in `order_status_logs` for every status change.
- [x] Relevant timestamp fields (e.g., `confirmed_at`, `shipping_at`) are updated on status change.
- [x] All unit and integration tests for the status workflow are passing.