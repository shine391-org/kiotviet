---
title: "TASK 08: Cancel Order Implementation (CodeIgniter 4)"
id: "TASK-08-CANCEL-ORDER-CI4"
priority: "P1 (High)"
estimated_effort: "1 day"
dependencies: "TASK-06-STATUS-MANAGEMENT-CI4"
status: "Done"
module: "Order Workflow"
type: "Implementation Task"
tags: ["task", "orders", "cancel", "inventory", "status-management", "API", "backend", "codeigniter"]
purpose: "Implement the order cancellation logic in CodeIgniter 4, including validating cancellable statuses, conditionally restoring inventory, and providing a dedicated API endpoint."
location: "docs/tasks/MAIN_MODULES/07_TASK"
---

# TASK 08: Cancel Order Implementation (CodeIgniter 4)

**Priority:** P1 (High)
**Estimated Effort:** 1 day
**Dependencies:** TASK 06 (Status Management)
**Status:** Done

---

## 🎯 OBJECTIVE

Implement the business logic for **cancelling an order**. This process is handled by delegating to the core `OrderStatusService`, which ensures that all status transition rules and side-effects (like inventory restoration) are correctly applied.

---

## 📋 CANCELLATION RULES

The ability to cancel an order is determined by its current status.

-   **Cancellable Statuses**:
    -   ✅ `draft`
    -   ✅ `confirmed`
    -   ✅ `processing`
    -   ✅ `shipping`
-   **Non-Cancellable Statuses**:
    -   ❌ `delivered`
    -   ❌ `completed`
    -   ❌ `cancelled`

**Key Side-Effect**: Inventory is automatically restored **only if** the order is cancelled from a `processing` or `shipping` status, as this is when stock has already been deducted.

---

## 🏗️ ARCHITECTURE (CodeIgniter 4)

The cancellation logic is implemented as a thin facade over the more generic `OrderStatusService`. This keeps the `OrderCancellationService` simple and focused on its specific task.

### **Service: `OrderCancellationService.php`**

This service's primary role is to validate that an order is in a cancellable state before passing the request to the `OrderStatusService`.

```php
<?php
namespace App\Services\Orders;
use InvalidArgumentException;

class OrderCancellationService
{
    private const CANCELLABLE = ['draft', 'confirmed', 'processing', 'shipping'];

    protected OrderStatusService $statusService;
    protected OrderRepositoryAdapter $orders;

    public function __construct(...)
    {
        $this->statusService = service('orderStatusService');
        // ...
    }

    public function cancel(int $orderId, ?string $reason = null, ?int $userId = null): array
    {
        // 1. Fetch the order
        $order = $this->orders->find($orderId);
        
        // 2. Validate if the current status is in the CANCELLABLE list
        if (!in_array($order['status'] ?? 'draft', self::CANCELLABLE, true)) {
            throw new InvalidArgumentException('Order cannot be cancelled from current status');
        }

        // 3. Delegate the actual status update to the core status service
        return $this->statusService->updateStatus($orderId, 'cancelled', $userId, $reason);
    }
}
```

### **Core Logic in `OrderStatusService`**

The `OrderStatusService` handles the heavy lifting when it receives the `cancelled` status transition:
1.  It checks if inventory needs to be restored (i.e., if the `from` status was `processing` or `shipping`).
2.  It calls the `restoreInventory` method, which increases `quantity_on_hand` and logs an `adjustment` movement.
3.  It updates the order's `status` to `cancelled` and sets the `cancelled_at` timestamp and `cancellation_reason`.
4.  It logs the status change in `order_status_logs`.

### **Controller: `OrderCancellationController.php`**

A dedicated, thin controller provides the API endpoint.

```php
// app/Controllers/Api/OrderCancellationController.php
class OrderCancellationController extends BaseController
{
    use ResponseTrait;
    protected OrderCancellationService $service;

    public function __construct()
    {
        $this->service = service('orderCancellationService');
    }

    /** @agent-use: POST /api/orders/{id}/cancel */
    public function cancel($id)
    {
        $reason = $this->request->getJSON(true)['reason'] ?? null;
        return $this->wrap(fn () => $this->respond(
            $this->service->cancel((int) $id, $reason, $this->userId())
        ));
    }
}
```

---

## 🧪 TESTING

-   **`OrderCancellationServiceTest.php`**: Contains unit tests that verify:
    -   An order in a `processing` state can be cancelled, and that inventory is correctly restored.
    -   An order in a `completed` state cannot be cancelled and throws the correct exception.
-   **Integration Tests**: Test the `POST /api/orders/{id}/cancel` endpoint to ensure the entire flow, including validation and side-effects, works correctly through an API call.

---

## 📝 ACCEPTANCE CRITERIA

- [x] Orders can be cancelled from `draft`, `confirmed`, `processing`, and `shipping` statuses.
- [x] Attempting to cancel an order in a `delivered` or `completed` state results in a validation error.
- [x] Inventory is correctly restored **only if** the order was cancelled from a `processing` or `shipping` status.
- [x] The `cancellation_reason` and `cancelled_at` fields in the `orders` table are correctly populated.
- [x] A corresponding status change is logged in `order_status_logs`.
- [x] The `POST /api/orders/{id}/cancel` endpoint is functional and secure.
- [x] All relevant unit and integration tests are passing.
