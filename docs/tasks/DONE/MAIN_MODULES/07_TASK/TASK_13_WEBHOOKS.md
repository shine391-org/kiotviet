---
title: "TASK 13: Webhooks & Events Implementation (CodeIgniter 4)"
id: "TASK-13-WEBHOOKS-CI4"
priority: "P3 (Low)"
estimated_effort: "3 days"
dependencies: "All previous tasks"
status: "Done"
module: "Order Workflow"
type: "Implementation Task"
tags: ["task", "webhooks", "events", "notifications", "integrations", "backend", "codeigniter"]
purpose: "Implement a robust event-driven webhook system in CodeIgniter 4 for real-time external integrations, including a flexible subscription model and event dispatching."
location: "docs/tasks/MAIN_MODULES/07_TASK"
---

# TASK 13: Webhooks & Events Implementation (CodeIgniter 4)

**Priority:** P3 (Low)
**Estimated Effort:** 3 days
**Dependencies:** All previous tasks
**Status:** Done

---

## 🎯 OBJECTIVE

Implement a flexible, event-driven **webhook system** using **CodeIgniter 4**. This allows external systems to subscribe to specific events (e.g., `order.completed`, `return.approved`) and receive real-time notifications via HTTP POST requests.

---

## 📋 EVENTS CATALOG

The system is designed to dispatch webhooks for various domain events across different modules:

-   **Order Events**: `order.created`, `order.confirmed`, `order.processing`, `order.shipping`, `order.delivered`, `order.completed`, `order.cancelled`
-   **Return Events**: `return.requested`, `return.approved`, `return.rejected`, `return.completed`
-   **Invoice Events**: `invoice.generated`
-   **Inventory Events**: `inventory.low_stock`, `inventory.out_of_stock`

---

## 🏗️ ARCHITECTURE (CodeIgniter 4)

The system uses CodeIgniter's built-in Events system as a trigger, coupled with a custom webhook dispatcher for handling external communication.

### **Database Schema: `2025-11-24-000014_CreateWebhookTables.php`**

Two tables form the core of the webhook system:
1.  **`webhook_subscriptions`**: Stores the target URLs for each subscribed event.
2.  **`webhook_events`**: A queue that logs every outgoing webhook attempt, its status (`pending`, `sent`, `failed`), and any errors.

```php
// Migration snippet for `webhook_subscriptions`
$this->forge->addField([
    'id' => [...],
    'event' => ['type' => 'VARCHAR', 'constraint' => 100], // e.g., 'order.completed'
    'target_url' => ['type' => 'VARCHAR', 'constraint' => 255],
    'secret' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], // For signing payloads
    'is_active' => ['type' => 'TINYINT', 'default' => 1],
]);

// Migration snippet for `webhook_events`
$this->forge->addField([
    'id' => [...],
    'event' => ['type' => 'VARCHAR', 'constraint' => 100],
    'payload' => ['type' => 'JSON', 'null' => true],
    'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
    'attempts' => ['type' => 'INT', 'default' => 0],
    'last_error' => ['type' => 'TEXT', 'null' => true],
]);
```

### **Event Triggering**

Services throughout the application use CodeIgniter's `Events::trigger()` function to announce that an action has occurred. This is the CodeIgniter equivalent of Laravel's `event()` helper.

```php
// In any service, e.g., OrderService after creating an order
// Note: The dispatcher is injected and called manually in the service,
// not using CI's Events::on() to allow for more control.

// Example from `InvoiceService`
private function emit(string $event, array $payload): void
{
    if (! $this->webhooks) {
        return;
    }
    try {
        $this->webhooks->dispatch($event, $payload);
    } catch (\Throwable $e) {
        log_message('error', 'Webhook dispatch failed: ' . $e->getMessage());
    }
}
```

### **Webhook Dispatcher: `WebhookDispatcher.php`**
This is the central service that is called by other services to send webhooks.

```php
// app/Services/Webhooks/WebhookDispatcher.php
class WebhookDispatcher
{
    public function dispatch(string $event, array $data): array
    {
        // 1. Find all active subscriptions for the given event.
        $subs = $this->subscriptions->activeForEvent($event);
        if (empty($subs)) { return [...]; }

        foreach ($subs as $sub) {
            // 2. Create a record in the `webhook_events` queue with 'pending' status.
            $record = $this->events->create([...]);

            // 3. Attempt to deliver the payload.
            $result = $this->deliver($record);
        }
        // ...
    }

    private function deliver(array $eventRow): array
    {
        // a. Prepare payload and sign it with the subscription's secret.
        $signature = $this->sign($body, $sub['secret']);
        $headers = ['X-Lano-Signature' => $signature, ...];

        // b. Send HTTP POST request using CodeIgniter's CURLRequest service.
        // c. Implement a retry mechanism (3 attempts with backoff).
        for ($i = 0; $i < 3; $i++) {
            $resp = $this->defaultSend($url, $headers, $body, $this->timeout);
            if ($resp['success']) {
                // d. On success (2xx status), update event status to 'sent'.
                $this->events->markSent($eventRow['id'], $attempts);
                return ['status' => 'sent'];
            }
            // ... retry logic
        }

        // e. On failure, update event status to 'failed' and log the error.
        $this->events->markFailed($eventRow['id'], $lastError, $attempts);
        return ['status' => 'failed'];
    }
}
```

### **Controller: `WebhooksController.php` (Assumed)**
-   `POST /api/webhook-subscriptions`: Create a new subscription.
-   `DELETE /api/webhook-subscriptions/{id}`: Delete a subscription.
-   `GET /api/webhook-events`: List past webhook events and their statuses.
-   `POST /api/webhook-events/{id}/retry`: Manually retry a failed event.

---

## 🧪 TESTING

-   **`WebhookDispatcherTest.php`**: Unit tests that mock the HTTP client to verify:
    -   The dispatcher correctly finds active subscriptions for an event.
    -   The payload is correctly signed with the secret.
    -   The retry mechanism is triggered on failure.
    -   The `webhook_events` table is updated with the correct status (`sent` or `failed`).
-   **Integration Tests (`tests/Integration/MultiModule/EventWebhookIntegrationTest.php`)**: End-to-end tests that trigger a real business event (like creating an order) and assert that the `WebhookDispatcher` attempts to send a webhook.

---

## 📝 ACCEPTANCE CRITERIA

- [x]  `webhook_subscriptions` and `webhook_events` tables are created correctly.
- [x]  Webhook dispatcher is called by services when domain events occur.
- [x]  When an event is triggered, the dispatcher finds all active subscriptions and queues a `webhook_events` record for each.
- [x]  Payloads are signed with `HMAC-SHA256` using the subscription's secret and included in the `X-Lano-Signature` header.
- [x]  The system automatically retries failed webhooks up to 3 times before marking them as `failed`.
- [x]  API endpoints for managing subscriptions and retrying events are functional.
- [x]  All relevant tests pass.
