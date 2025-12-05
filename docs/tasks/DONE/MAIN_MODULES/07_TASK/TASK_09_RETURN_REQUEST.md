---
title: "TASK 09: Return Request Implementation (CodeIgniter 4)"
id: "TASK-09-RETURN-REQUEST-CI4"
priority: "P2 (Medium)"
estimated_effort: "2 days"
dependencies: "TASK-05-ORDER-CREATE-CI4"
status: "Done"
module: "Order Workflow"
type: "Implementation Task"
tags: ["task", "returns", "request", "validation", "refund", "API", "backend", "codeigniter"]
purpose: "Implement the creation of customer return requests in CodeIgniter 4, including comprehensive validation, return number generation, and initial refund calculation."
location: "docs/tasks/MAIN_MODULES/07_TASK"
---

# TASK 09: Return Request Implementation (CodeIgniter 4)

**Priority:** P2 (Medium)
**Estimated Effort:** 2 days
**Dependencies:** TASK 05 (Order Create)
**Status:** Done

---

## 🎯 OBJECTIVE

Implement the API endpoint and business logic for creating a **customer return request** using **CodeIgniter 4**. This includes robust validation to ensure the integrity of the return process.

---

## 📋 VALIDATION RULES

A dedicated `ReturnValidator` class enforces the following critical business rules before a return request can be created:

### **Order-Level Validation**
-   ✅ **Order Exists**: The specified `order_id` must correspond to an existing order.
-   ✅ **Order Status**: The order must have a status of `completed`.
-   ✅ **Return Window**: The request must be within the allowed return window (e.g., 30 days from `completed_at`).
-   ✅ **Customer Ownership**: The user initiating the return must be the customer who placed the order.

### **Item-Level Validation**
-   ✅ **At Least One Item**: The request must include at least one item to be returned.
-   ✅ **Item Belongs to Order**: Each `order_item_id` must belong to the specified `order_id`.
-   ✅ **Quantity Check**: The `quantity_returned` for an item cannot exceed the original quantity purchased.
-   ✅ **No Over-Returning**: The system checks previous returns for the same order to prevent returning more items than are available.

---

## 🏗️ ARCHITECTURE (CodeIgniter 4)

### **Validator: `ReturnValidator.php`**
This class is responsible for validating the incoming payload for a new return request. It checks all business rules and throws an `InvalidArgumentException` if any rule is violated.

```php
// app/Validators/ReturnValidator.php
class ReturnValidator
{
    public function validateCreate(array $input): array
    {
        $rules = [
            'order_id' => 'required|integer|greater_than_equal_to[1]',
            'customer_id' => 'required|integer|greater_than_equal_to[1]',
            'items' => 'required',
            'reason' => 'required|in_list[defective,wrong_item,not_satisfied,other]',
        ];
        // ... run validation
        
        $items = $this->normalizeItems($input['items']);
        if (empty($items)) {
            throw new InvalidArgumentException('Return must have at least one item');
        }

        return [...]; // Sanitized data
    }
}
```

### **Service: `ReturnService.php`**
The `ReturnService` orchestrates the creation of the return request.

```php
// app/Services/Returns/ReturnService.php
class ReturnService
{
    public function create(array $payload): array
    {
        // 1. Validate the incoming payload.
        $validated = $this->validator->validateCreate($payload);

        // 2. Fetch the associated order and its items.
        $order = $this->repo->orderWithItems($validated['order_id']);

        // 3. Assert business rules (order completed, within window, customer match).
        $this->assertOrderCompleted($order);
        $this->assertWithinWindow($order);
        // ...

        // 4. Build a list of items to be returned, calculating totals and checking available quantities.
        $items = $this->buildReturnItems($order, $validated['items']);
        $returnAmount = array_sum(array_column($items, 'line_total'));

        // 5. Generate a unique return number (e.g., TH-123-1).
        $number = $this->repo->nextNumber($validated['order_id']);

        // 6. Persist the return request (`returns` and `return_items` tables) in a transaction.
        $created = $this->repo->create($returnRow, $itemRows);

        // 7. Dispatch a 'return.requested' event.
        $this->emit('return.requested', $transformed);

        return ['success' => true, 'data' => $transformed];
    }
}
```

### **Controller: `ReturnsController.php`**
A thin controller that exposes the `POST /api/returns` endpoint.

```php
// app/Controllers/Api/ReturnsController.php
class ReturnsController extends BaseController
{
    // ... constructor and other methods

    /** Create return. @agent-use: POST /api/returns */
    public function create()
    {
        return $this->wrap(fn () => $this->respondCreated(
            $this->service->create($this->safeInput())
        ));
    }
}
```

---

## 🧪 TESTING

-   **`ReturnValidatorTest.php`**: Contains unit tests for the validator, ensuring all rules for the creation payload are enforced.
-   **`ReturnServiceTest.php`**: Unit tests for the service layer, verifying:
    -   Successful creation of a return request.
    -   Accurate calculation of `return_amount`.
    -   Correct generation of the return number.
    -   Exceptions are thrown for invalid scenarios (e.g., attempting to return an incomplete order or exceeding the purchased quantity).
-   **Integration Test (`tests/Integration/Returns/ReturnsApiTest.php`)**: A feature test that simulates a real API call to `POST /api/returns` to ensure the entire process works end-to-end.

---

## 📝 ACCEPTANCE CRITERIA

- [x] The `POST /api/returns` endpoint is functional.
- [x] A unique return number (`TH-{order_id}-{counter}`) is generated for each request.
- [x] The system correctly validates that the order is `completed` and within the 30-day return window.
- [x] The system prevents returning more items than were originally purchased, accounting for previous returns on the same order.
- [x] The `return_amount` is calculated correctly based on the prices of the returned items.
- [x] A new record is created in the `returns` table with a `pending` status.
- [x] Corresponding records are created in the `return_items` table.
- [x] All relevant unit and integration tests pass.
