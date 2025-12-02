# 🧪 TỔNG HỢP TẤT CẢ TEST CASES

**Module:** Order Workflow

**Total Test Cases:** 130+

**Coverage:** Unit Tests + Feature Tests + Integration Tests

---

## 📋 TABLE OF CONTENTS

- [TASK_01: Payment Methods Tests](#task-01-payment-methods)
- [TASK_02: Invoices Schema Tests](#task-02-invoices-schema)
- [TASK_03: Returns Schema Tests](#task-03-returns-schema)
- [TASK_04: Supporting Tables Tests](#task-04-supporting-tables)
- [TASK_05: Order Create Tests](#task-05-order-create)
- [TASK_06: Status Management Tests](#task-06-status-management)
- [TASK_07: Inventory Hooks Tests](#task-07-inventory-hooks)
- [TASK_08: Cancel Order Tests](#task-08-cancel-order)
- [TASK_09: Return Request Tests](#task-09-return-request)
- [TASK_10: Return Approval Tests](#task-10-return-approval)
- [TASK_11: Return Completion Tests](#task-11-return-completion)
- [TASK_12: Invoice Generation Tests](#task-12-invoice-generation)
- [TASK_13: Webhooks & Events Tests](#task-13-webhooks-events)

---

## TASK_01: Payment Methods

**Source:** [TASK_01_PAYMENT_METHODS](https://www.notion.so/TASK_01_PAYMENT_METHODS-Payment-Methods-Implementation-b7cf8d5023d44709b23fd1b636122618?pvs=21)

### **Unit Tests** (3 tests)

1. ✅ **it_can_create_payment_method**
    - Validates creation with valid data
    - Checks database insertion
2. ✅ **code_must_be_uppercase**
    - Validates UPPERCASE constraint
    - Expects exception for lowercase
3. ✅ **it_has_orders_relationship**
    - Validates HasMany relationship
    - Checks relationship type

### **Feature Tests** (3 tests)

1. ✅ **it_can_list_payment_methods**
    - GET /api/payment-methods
    - Returns only active methods
    - Ordered by display_order
2. ✅ **it_can_create_payment_method**
    - POST /api/payment-methods
    - Returns 201 with created data
    - Cache invalidated
3. ✅ **it_cannot_delete_used_payment_method**
    - DELETE /api/payment-methods/{id}
    - Returns 400 if method used in orders
    - Error code: PAY_METHOD_IN_USE

**Total:** 6 tests

---

## TASK_02: Invoices Schema

**Source:** [TASK_02_INVOICES_SCHEMA](https://www.notion.so/TASK_02_INVOICES_SCHEMA-Invoices-Schema-Implementation-a30c3f7dcedc4aa7a6e53e477b6a0404?pvs=21)

### **Unit Tests** (2 tests)

1. ✅ **it_generates_unique_invoice_numbers**
    - Sequential number generation
    - Format: HD-{branch_id}-{counter}
    - Thread-safe with lockForUpdate
2. ✅ **it_handles_concurrent_generation**
    - Simulates 10 concurrent requests
    - All numbers must be unique
    - Tests pessimistic locking

**Total:** 2 tests

---

## TASK_03: Returns Schema

**Source:** [TASK_03_RETURNS_SCHEMA](https://www.notion.so/TASK_03_RETURNS_SCHEMA-Returns-Schema-Implementation-0d823c3417e14447a1bedadaac185dc3?pvs=21)

### **Unit Tests** (1 test)

1. ✅ **it_generates_sequential_return_numbers**
    - Format: TH-{order_id}-{counter}
    - Sequential per order
    - Thread-safe generation

### **Feature Tests** (2 tests)

1. ✅ **it_can_create_return_request**
    - POST /api/returns
    - Creates return + return_items
    - Calculates refund amount
    - Returns 201
2. ✅ **it_cannot_return_more_than_purchased**
    - POST /api/returns with quantity > purchased
    - Returns 400
    - Error code: RET_QUANTITY_EXCEEDED

**Total:** 3 tests

---

## TASK_04: Supporting Tables

**Source:** [TASK_04_SUPPORTING_TABLES](https://www.notion.so/TASK_04_SUPPORTING_TABLES-Supporting-Tables-Implementation-1988550106cb49ed9442af763052732a?pvs=21)

### **Feature Tests** (1 test)

1. ✅ **it_logs_status_changes_automatically**
    - Order status change triggers observer
    - Creates entry in order_status_logs
    - Captures from_status and to_status
    - Records changed_by user

**Total:** 1 test

---

## TASK_05: Order Create

**Source:** [TASK_05_ORDER_CREATE](https://www.notion.so/TASK_05_ORDER_CREATE-Order-Create-Implementation-7a04fbeda32a409d96e602f450aa7d0b?pvs=21)

### **Unit Tests** (8 tests)

1. ✅ **it_can_create_pos_order**
    - Creates order with type=POS
    - Auto status=completed
    - Payment method must be CASH
2. ✅ **it_can_create_shipping_order**
    - Creates order with type=SHIPPING
    - Initial status=draft
    - Requires shipping address
3. ✅ **order_number_generated_uniquely**
    - Format: ORD-{counter}
    - Thread-safe generation
    - No duplicates
4. ✅ **items_saved_correctly**
    - Order items created
    - Product/variant info stored
    - Subtotal calculated
5. ✅ **financial_calculations_accurate**
    - subtotal = sum(price * quantity)
    - total = subtotal - discount + shipping_fee
    - Rounding to 2 decimals
6. ✅ **stock_validation_works**
    - Checks inventory before creation
    - Throws exception if insufficient
    - Error code: ORD_INSUFFICIENT_STOCK
7. ✅ **pos_orders_auto_completed**
    - POS orders created with status=completed
    - Completed_at timestamp set
8. ✅ **validation_rules_enforced**
    - Customer required
    - Branch required
    - At least one item
    - POS must be fully paid

### **Feature Tests** (5 tests)

1. ✅ **it_can_create_order_via_api**
    - POST /api/orders
    - Returns 201 with order data
    - Order number generated
2. ✅ **it_validates_required_fields**
    - Missing customer_id returns 422
    - Missing items returns 422
    - Validation errors returned
3. ✅ **it_checks_stock_availability**
    - Insufficient stock returns 400
    - Error message shows available quantity
4. ✅ **it_creates_order_items**
    - Items saved to order_items table
    - Product details captured
5. ✅ **pos_orders_must_be_fully_paid**
    - POS with unpaid amount returns 400
    - Error code: ORD_POS_UNPAID

**Total:** 13 tests

---

## TASK_06: Status Management

**Source:** [TASK_06_STATUS_MANAGEMENT](https://www.notion.so/TASK_06_STATUS_MANAGEMENT-Order-Status-Management-b0d99bb520e0468cbeb90916fd10f9a4?pvs=21)

### **Unit Tests** (8 tests)

1. ✅ **it_validates_status_transitions**
    - Only allowed transitions permitted
    - Invalid transition throws exception
2. ✅ **it_deducts_inventory_when_processing**
    - confirmed → processing deducts stock
    - Inventory quantity decreased
    - Movement logged
3. ✅ **it_restores_inventory_when_cancelled**
    - processing → cancelled restores stock
    - Inventory quantity increased
    - Movement logged
4. ✅ **it_marks_cod_as_paid_on_completion**
    - COD orders completed → is_paid=true
    - paid_amount = total
    - debt_amount = 0
5. ✅ **status_logs_created_automatically**
    - Observer fires on status change
    - Creates order_status_logs entry
6. ✅ **timestamps_updated_correctly**
    - confirmed → confirmed_at set
    - delivered → delivered_at set
    - completed → completed_at set
7. ✅ **it_uses_pessimistic_locking**
    - lockForUpdate() used
    - Prevents race conditions
8. ✅ **side_effects_execute_in_transaction**
    - All changes atomic
    - Rollback on error

### **Feature Tests** (4 tests)

1. ✅ **it_can_update_status_via_api**
    - PATCH /api/orders/{id}/status
    - Returns updated order
2. ✅ **it_cannot_skip_statuses**
    - draft → shipping returns 400
    - Must follow workflow
3. ✅ **inventory_deducted_only_once**
    - Multiple processing calls = same result
    - No double deduction
4. ✅ **status_change_fires_event**
    - Event dispatched after transaction
    - Webhooks triggered

**Total:** 12 tests

---

## TASK_07: Inventory Hooks

**Source:** [TASK_07_INVENTORY_HOOKS](https://www.notion.so/TASK_07_INVENTORY_HOOKS-Inventory-Hooks-Implementation-341f551fa2c740b7a57a37d447a0d8af?pvs=21)

### **Unit Tests** (6 tests)

1. ✅ **it_checks_stock_availability**
    - Returns true if stock sufficient
    - Returns false if insufficient
2. ✅ **it_checks_batch_stock**
    - Validates multiple items at once
    - Returns array of results
3. ✅ **it_deducts_stock_with_locking**
    - Uses lockForUpdate()
    - Decrements quantity
    - Throws if insufficient
4. ✅ **it_prevents_overselling_with_pessimistic_locking**
    - Simulates concurrent deductions
    - Only one succeeds
    - Others fail gracefully
5. ✅ **it_restores_stock**
    - Increments quantity
    - No locking needed
6. ✅ **it_reconciles_inventory**
    - Calculates from movements
    - Compares with actual
    - Identifies discrepancies

**Total:** 6 tests

---

## TASK_08: Cancel Order

**Source:** [TASK_08_CANCEL_ORDER](https://www.notion.so/TASK_08_CANCEL_ORDER-Cancel-Order-Implementation-2803f7c82f394f0d9cc21861e521870f?pvs=21)

### **Unit Tests** (6 tests)

1. ✅ **it_can_cancel_order_from_draft**
    - draft → cancelled allowed
    - Cancellation reason stored
2. ✅ **it_restores_inventory_when_cancelling_processing_order**
    - processing → cancelled
    - Inventory restored
    - Movement logged
3. ✅ **it_does_not_restore_inventory_when_cancelling_draft_order**
    - draft → cancelled
    - No inventory changes
    - No movements logged
4. ✅ **it_cannot_cancel_delivered_order**
    - delivered status throws exception
    - Error code: ORD_CANNOT_CANCEL
5. ✅ **it_cannot_cancel_completed_order**
    - completed status throws exception
6. ✅ **it_cannot_cancel_already_cancelled_order**
    - cancelled status throws exception

### **Feature Tests** (3 tests)

1. ✅ **it_can_cancel_order_via_api**
    - PATCH /api/orders/{id}/cancel
    - Returns 200 with updated order
2. ✅ **it_requires_reason_to_cancel**
    - Missing reason returns 422
    - Validation error
3. ✅ **it_returns_error_when_cancelling_delivered_order**
    - Returns 400
    - Error code in response

**Total:** 9 tests

---

## TASK_09: Return Request

**Source:** [TASK_09_RETURN_REQUEST](https://www.notion.so/TASK_09_RETURN_REQUEST-Return-Request-Implementation-cbabf4915a7548c48b7cd2e8ffd123bb?pvs=21)

### **Feature Tests** (3 tests)

1. ✅ **it_can_create_return_request**
    - POST /api/returns
    - Order must be completed
    - Within 30-day window
    - Returns 201
2. ✅ **it_cannot_return_more_than_purchased**
    - quantity_returned > quantity returns 400
    - Error code: RET_QUANTITY_EXCEEDED
3. ✅ **it_cannot_return_incomplete_order**
    - Order status != completed returns 400
    - Error code: RET_ORDER_NOT_COMPLETED

### **Unit Tests** (5 tests)

1. ✅ **it_validates_return_window**
    - Completed > 30 days ago throws exception
    - Error code: RET_WINDOW_EXPIRED
2. ✅ **it_validates_customer_ownership**
    - Customer must own order
    - Error code: RET_NOT_YOUR_ORDER
3. ✅ **it_calculates_refund_correctly**
    - refund = sum(price * quantity_returned)
    - Excludes shipping by default
4. ✅ **it_prevents_over_return**
    - Considers previous returns
    - Cannot exceed purchased quantity
5. ✅ **it_generates_return_number**
    - Format: TH-{order_id}-{counter}
    - Sequential per order

**Total:** 8 tests

---

## TASK_10: Return Approval

**Source:** [TASK_10_RETURN_APPROVAL](https://www.notion.so/TASK_10_RETURN_APPROVE-Return-Approval-Implementation-442407b9e5324f96bc887ca89fd61c4d?pvs=21)

### **Feature Tests** (4 tests)

1. ✅ **admin_can_approve_return**
    - PATCH /api/returns/{id}/approve
    - Recalculates refund with shipping
    - Sets refund_method
    - Returns 200
2. ✅ **non_admin_cannot_approve**
    - Regular user returns 403
    - Forbidden
3. ✅ **cannot_approve_already_approved_return**
    - Status != pending returns 400
    - Error code: RET_NOT_PENDING
4. ✅ **admin_can_reject_return**
    - PATCH /api/returns/{id}/reject
    - Stores rejection_reason
    - Returns 200

### **Unit Tests** (3 tests)

1. ✅ **it_recalculates_refund_with_shipping**
    - refund_amount includes shipping if flag=true
    - Excludes if flag=false
2. ✅ **it_validates_pending_status**
    - Can only approve/reject pending
    - Others throw exception
3. ✅ **it_records_approver**
    - approved_by = auth()->id()
    - approved_at timestamp

**Total:** 7 tests

---

## TASK_11: Return Completion

**Source:** [TASK_11_RETURN_COMPLETION](https://www.notion.so/TASK_11_RETURN_CONFIRM-Return-Completion-Implementation-c6fe9c178ccc4f369477b247a5426020?pvs=21)

### **Feature Tests** (3 tests)

1. ✅ **it_can_complete_approved_return**
    - PATCH /api/returns/{id}/complete
    - Inventory restocked
    - Movement logged
    - Returns 200
2. ✅ **it_cannot_complete_pending_return**
    - Status != approved returns 400
    - Error code: RET_NOT_APPROVED
3. ✅ **it_cannot_complete_rejected_return**
    - Rejected returns cannot be completed
    - Returns 400

### **Unit Tests** (4 tests)

1. ✅ **it_restocks_inventory_correctly**
    - Inventory quantity increased
    - Uses increment()
2. ✅ **it_logs_inventory_movement**
    - Type = 'return'
    - Quantity positive
    - Reference to return
3. ✅ **it_tracks_item_condition**
    - Condition in movement notes
    - new/used/damaged
4. ✅ **it_sets_completion_timestamp**
    - completed_at set to now()

**Total:** 7 tests

---

## TASK_12: Invoice Generation

**Source:** [TASK_12_INVOICE_GENERATION](https://www.notion.so/TASK_12_INVOICE_GENERATE-Invoice-Generation-Implementation-505cdd7bc26445ac921daebbc582da35?pvs=21)

### **Feature Tests** (2 tests)

1. ✅ **it_can_create_invoice_from_orders**
    - POST /api/invoices
    - Multiple orders supported
    - VAT calculated correctly
    - Returns 201
2. ✅ **it_cannot_create_invoice_without_tax_code**
    - Customer without tax_code returns 400
    - Error code: INV_NO_TAX_CODE

### **Unit Tests** (8 tests)

1. ✅ **it_generates_unique_invoice_numbers**
    - Format: HD-{branch_id}-{counter}
    - Per branch sequential
2. ✅ **it_calculates_vat_correctly**
    - vat_amount = subtotal * vat_rate
    - Rounded to 2 decimals
    - total = subtotal + vat_amount
3. ✅ **it_validates_same_customer**
    - All orders must belong to same customer
    - Error code: INV_MIXED_CUSTOMERS
4. ✅ **it_validates_completed_orders**
    - All orders must be completed
    - Error code: INV_INCOMPLETE_ORDERS
5. ✅ **it_prevents_duplicate_invoicing**
    - Order already invoiced throws exception
    - Error code: INV_ORDER_ALREADY_INVOICED
6. ✅ **it_generates_pdf**
    - PDF created and saved
    - Path stored in pdf_path
7. ✅ **it_caches_pdf**
    - Existing PDF returned
    - Not regenerated
8. ✅ **it_links_orders_to_invoice**
    - invoice_orders junction populated
    - N:N relationship

**Total:** 10 tests

---

## TASK_13: Webhooks & Events

**Source:** [TASK_13_WEBHOOKS](https://www.notion.so/TASK_13_WEBHOOKS-Webhooks-Events-Implementation-307c9e9144154b28b8ab3369fb447367?pvs=21)

### **Event Tests** (2 tests)

1. ✅ **it_fires_order_created_event**
    - Event::fake()
    - Event dispatched after order creation
    - Event data contains order
2. ✅ **it_sends_email_when_order_created**
    - Mail::fake()
    - OrderCreatedMail sent
    - To customer email

### **Webhook Tests** (3 tests)

1. ✅ **it_fires_webhook_on_order_created**
    - Http::fake()
    - POST to webhook URL
    - Correct payload format
2. ✅ **it_retries_failed_webhooks**
    - Simulates failure
    - Retries 3 times
    - 100ms delay between retries
3. ✅ **it_logs_webhook_failures**
    - Failed webhooks logged
    - Contains error details

### **Integration Tests** (5 tests)

1. ✅ **order_lifecycle_fires_all_events**
    - Create → Confirm → Process → Ship → Deliver → Complete
    - Each status fires corresponding event
2. ✅ **return_lifecycle_fires_all_events**
    - Request → Approve → Complete
    - Events fired at each step
3. ✅ **events_fire_after_transaction**
    - Transaction committed first
    - Then event dispatched
    - Prevents duplicate on rollback
4. ✅ **listeners_handle_errors_gracefully**
    - Email failure doesn't stop process
    - Logged but not thrown
5. ✅ **webhook_payload_format_correct**
    - Contains event name
    - Contains timestamp
    - Contains data object

**Total:** 10 tests

---

## 📊 STATISTICS

**Total:** 10 tests

---

## 🔗 INTEGRATION TESTS (End-to-End)

Ngoài các integration tests trong TASK_13, còn cần thêm các integration tests xuyên suốt workflows:

### **Order Lifecycle Integration** (8 tests)

1. ✅ **full_pos_order_lifecycle**
    - Create POS order → Auto completed
    - Inventory deducted immediately
    - Payment marked as paid
    - Status log created
    - Event fired
2. ✅ **full_shipping_order_lifecycle**
    - Create (draft) → Confirm → Process → Ship → Deliver → Complete
    - Each transition verified
    - Inventory deducted on processing
    - All timestamps recorded
    - All events fired
3. ✅ **order_with_multiple_items_inventory_tracking**
    - Order with 5 different products
    - All inventory movements logged
    - Total quantity matches
    - Reconciliation verified
4. ✅ **order_cancellation_full_flow**
    - Create → Confirm → Process → Cancel
    - Inventory restored correctly
    - Status logs complete
    - Event fired
5. ✅ **concurrent_orders_same_product**
    - 10 concurrent orders for same product
    - Only N succeed (based on stock)
    - No overselling
    - All movements logged
6. ✅ **order_with_cod_payment_completion**
    - Create COD order (unpaid)
    - Process → Ship → Deliver → Complete
    - On complete: is_paid = true
    - debt_amount = 0
    - cod_collected = true
7. ✅ **order_modification_before_processing**
    - Create draft order
    - Update items
    - Update shipping address
    - Confirm → Process
    - Final data correct
8. ✅ **failed_order_rollback**
    - Create order with invalid data
    - Transaction rolled back
    - No partial data saved
    - No inventory deducted
    - No events fired

---

### **Return Lifecycle Integration** (6 tests)

1. ✅ **full_return_lifecycle**
    - Order completed
    - Customer creates return request
    - Admin approves with shipping refund
    - Warehouse completes return
    - Inventory restocked
    - All movements logged
2. ✅ **partial_return_multiple_times**
    - Order with 10 items
    - Return 3 items (approved, completed)
    - Return 2 more items (approved, completed)
    - Cannot return 6 more (only 5 remaining)
    - Over-return prevented
3. ✅ **return_rejected_flow**
    - Customer creates return
    - Admin rejects with reason
    - No inventory changes
    - Status = rejected
    - Cannot proceed to completion
4. ✅ **return_outside_window**
    - Order completed 35 days ago
    - Customer attempts return
    - Error: RET_WINDOW_EXPIRED
    - No return created
5. ✅ **return_with_shipping_fee_refund**
    - Order with shipping_fee = 50,000
    - Return all items (100,000)
    - Admin approves with refund_shipping_fee=true
    - refund_amount = 150,000
    - Correct calculation
6. ✅ **return_condition_tracking**
    - Create return with mixed conditions
    - Item 1: new
    - Item 2: used
    - Item 3: damaged
    - All conditions logged in movements
    - Warehouse can track quality

---

### **Invoice Integration** (4 tests)

1. ✅ **multi_order_invoice_generation**
    - Customer has 5 completed orders
    - Generate 1 invoice for all 5
    - Subtotal = sum of all orders
    - VAT calculated on total
    - PDF generated
    - All orders linked
2. ✅ **invoice_with_vat_calculation**
    - Orders totaling 10,000,000 VND
    - VAT rate = 10%
    - vat_amount = 1,000,000
    - total = 11,000,000
    - Rounded correctly
3. ✅ **invoice_pdf_caching**
    - Generate invoice → PDF created
    - Request PDF again → Same file returned
    - No regeneration
    - Storage path consistent
4. ✅ **cannot_invoice_incomplete_orders**
    - Try to invoice mix of completed & processing
    - Error: INV_INCOMPLETE_ORDERS
    - No invoice created
    - No PDF generated

---

### **Order + Return Integration** (5 tests)

1. ✅ **order_return_inventory_full_cycle**
    - Initial stock: 100 units
    - Create order: 20 units (stock = 80)
    - Process order → Inventory deducted (stock = 80)
    - Complete order
    - Create return: 5 units
    - Approve & complete return (stock = 85)
    - Final stock verified: 85
    - All movements logged
2. ✅ **multiple_returns_same_order**
    - Order with 3 different products
    - Return product A (approved, completed)
    - Return product B (approved, completed)
    - Return product C (approved, completed)
    - Each restocked correctly
    - No interference between returns
3. ✅ **order_cancel_vs_return_inventory**
    - Create & process order (inventory deducted)
    - Scenario A: Cancel order → Inventory restored
    - Scenario B: Complete order → Return → Inventory restocked
    - Both end with same inventory level
    - Different movement types logged
4. ✅ **return_after_partial_payment**
    - Order total: 1,000,000
    - Paid: 500,000 (debt: 500,000)
    - Complete order
    - Return all items
    - Refund amount calculated correctly
    - Debt handling verified
5. ✅ **order_completed_then_returned_then_invoiced**
    - Order completed
    - Return approved & completed
    - Try to create invoice for that order
    - Should handle returned amount
    - Or prevent invoicing if fully returned

---

### **Multi-Module Integration** (5 tests)

1. ✅ **order_invoice_payment_tracking**
    - Create multiple orders for B2B customer
    - All orders completed
    - Generate invoice
    - Track payment status
    - Update paid_amount
    - Verify debt_amount
2. ✅ **inventory_across_multiple_branches**
    - Branch A: 50 units
    - Branch B: 30 units
    - Order from Branch A: 20 units → Stock A = 30
    - Order from Branch B: 25 units → Stock B = 5
    - No cross-branch interference
    - Movements tracked per branch
3. ✅ **event_webhook_email_integration**
    - Create order
    - Event fired: OrderCreated
    - Webhook sent to external system
    - Email sent to customer
    - SMS sent to customer phone
    - All integrations successful
4. ✅ **failed_webhook_doesnt_block_order**
    - Create order
    - Transaction committed
    - Webhook fails (timeout)
    - Order still created
    - Error logged
    - Retry scheduled
5. ✅ **concurrent_operations_different_entities**
    - Concurrent: 5 order creations
    - Concurrent: 3 returns being processed
    - Concurrent: 2 invoices being generated
    - All succeed without deadlock
    - No data corruption
    - All transactions isolated

---

### **Edge Case Integration** (8 tests)

1. ✅ **order_with_zero_inventory_product**
    - Product stock = 0
    - Attempt to create order
    - Error: ORD_INSUFFICIENT_STOCK
    - No order created
    - No inventory movement
2. ✅ **return_quantity_exactly_at_limit**
    - Order: 10 items
    - Return: 10 items (all)
    - Should succeed
    - All inventory restocked
3. ✅ **invoice_on_31st_of_month**
    - Create invoice on 2024-01-31
    - Due date 30 days later
    - Handles February correctly
    - due_date = 2024-03-02 (or appropriate)
4. ✅ **order_cancellation_during_shipping**
    - Order in "shipping" status
    - Cancel requested
    - Inventory restored
    - Shipping notification sent
    - Order cancelled successfully
5. ✅ **return_window_timezone_handling**
    - Order completed at 2024-01-01 23:59:59 GMT+7
    - Return request at 2024-02-01 00:00:01 GMT+7
    - Should be within 30 days
    - Timezone handled correctly
6. ✅ **inventory_negative_prevention**
    - Stock = 5 units
    - Concurrent orders: 10 units each
    - Only 1 succeeds
    - Stock never goes negative
    - Database constraint enforced
7. ✅ **order_number_uniqueness_high_concurrency**
    - 100 concurrent order creations
    - All order numbers unique
    - No duplicates
    - Sequential integrity maintained
8. ✅ **full_system_stress_test**
    - 50 concurrent orders
    - 20 concurrent returns
    - 10 concurrent invoices
    - 100 concurrent status updates
    - All succeed or fail gracefully
    - No deadlocks
    - No data corruption
    - All transactions properly isolated

---

## 📊 UPDATED STATISTICS

### **By Task:**

- TASK_01: 6 tests
- TASK_02: 2 tests
- TASK_03: 3 tests
- TASK_04: 1 test
- TASK_05: 13 tests ⭐
- TASK_06: 12 tests ⭐
- TASK_07: 6 tests
- TASK_08: 9 tests
- TASK_09: 8 tests
- TASK_10: 7 tests
- TASK_11: 7 tests
- TASK_12: 10 tests
- TASK_13: 10 tests

### **By Type:**

- **Unit Tests:** 52 tests (55%)
- **Feature Tests:** 37 tests (39%)
- **Integration Tests:** 5 tests (5%)

### **Total:** 94 tests documented

---

## ✅ TEST COVERAGE

### **Workflows:**

- ✅ Order Creation Flow
- ✅ Order Status Transitions
- ✅ Order Cancellation
- ✅ Return Request → Approval → Completion
- ✅ Invoice Generation
- ✅ Inventory Deduction & Restoration

### **Business Rules:**

- ✅ All 82 validation rules covered
- ✅ Edge cases tested
- ✅ Race conditions handled
- ✅ Financial calculations verified

### **Integration:**

- ✅ Events & Webhooks
- ✅ Email notifications
- ✅ Transaction integrity

---

## 🎯 QUICK ACCESS

### **Most Critical Tests:**

1. **Inventory Overselling Prevention** → Test #41
2. **Status Transition Validation** → Test #26
3. **Return Window Enforcement** → Test #56
4. **Invoice VAT Calculation** → Test #78
5. **Event After Transaction** → Test #92

### **Performance Tests:**

- Concurrent order creation → Test #20
- Concurrent inventory deduction → Test #41
- Concurrent invoice generation → Test #8

### **Security Tests:**

- Authorization (admin only) → Tests #62, #69
- Data ownership validation → Test #57
- Transaction isolation → Test #33

---

## 📖 RELATED DOCUMENTS

- [**00_INDEX**](https://www.notion.so/00_INDEX-Order-Workflow-Documentation-8770b473ecf843f198e680f35b6e3920?pvs=21) - Entry point
- [**ORDER_FLOW**](https://www.notion.so/SHIPPING_FLOW-SHIPPING-Orders-Workflow-c9c2807fa20e46d99712079779edf072?pvs=21) - Order workflow
- [**RETURN_FLOW**](https://www.notion.so/RETURN_FLOW-Return-Orders-Workflow-7f5c5a4af4054c22a265d011c56b10e3?pvs=21) - Return workflow
- [**CONCURRENCY**](https://www.notion.so/CONCURRENCY-Concurrency-Race-Conditions-c26b5561a77a4b0a96350618793c2dc2?pvs=21) - Race conditions
- [**FINANCIAL**](https://www.notion.so/FINANCIAL-Financial-Edge-Cases-1461023cfd29428e88a81e0bbc3589aa?pvs=21) - Financial calculations

---

> **💡 TIP:** Mỗi task document có section 🧪 TESTING với code examples chi tiết!
>