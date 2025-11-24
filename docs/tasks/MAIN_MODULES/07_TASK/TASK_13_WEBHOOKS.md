# TASK_13: Webhooks & Events Implementation

**Priority:** P3 (Low)

**Estimated Effort:** 4 days

**Dependencies:** All previous tasks

**Status:** Blocked

---

## 🎯 OBJECTIVE

Implement **event system** and webhooks for external integrations and notifications.

---

## 📋 EVENTS CATALOG

### **Order Events**

- `OrderCreated` - When new order created
- `OrderConfirmed` - When draft order confirmed
- `OrderProcessing` - When order starts processing
- `OrderShipped` - When order is shipped
- `OrderDelivered` - When order delivered
- `OrderCompleted` - When order finalized
- `OrderCancelled` - When order cancelled

### **Return Events**

- `ReturnRequested` - Customer creates return request
- `ReturnApproved` - Admin approves return
- `ReturnRejected` - Admin rejects return
- `ReturnCompleted` - Return processed & inventory restocked

### **Invoice Events**

- `InvoiceGenerated` - New invoice created
- `InvoiceOverdue` - Invoice past due date

### **Inventory Events**

- `LowStockAlert` - Stock below threshold
- `OutOfStock` - Stock depleted

---

## 🏗️ EVENT CLASSES

### **OrderCreated Event**

```php
<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class OrderCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function toWebhookPayload(): array
    {
        return [
            'event' => 'order.created',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'customer_id' => $this->order->customer_id,
                'branch_id' => $this->order->branch_id,
                'order_type' => $this->order->order_type,
                'total' => $this->order->total,
                'status' => $this->order->status,
                'payment_method' => $this->order->payment_method,
                'created_at' => $this->order->created_at->toIso8601String(),
            ]
        ];
    }
}
```

### **ReturnApproved Event**

```php
<?php

namespace App\Events;

use App\Models\Return;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReturnApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Return $return
    ) {}

    public function toWebhookPayload(): array
    {
        return [
            'event' => 'return.approved',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'return_id' => $this->return->id,
                'return_number' => $this->return->return_number,
                'order_id' => $this->return->order_id,
                'customer_id' => $this->return->customer_id,
                'refund_amount' => $this->return->refund_amount,
                'refund_method' => $this->return->refund_method,
                'approved_at' => $this->return->approved_at->toIso8601String(),
            ]
        ];
    }
}
```

---

## 🎯 LISTENERS

### **SendOrderCreatedNotification**

```php
<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Mail\OrderCreatedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendOrderCreatedNotification
{
    public function handle(OrderCreated $event)
    {
        $order = $event->order;
        
        // Send email to customer
        try {
            Mail::to($order->customer->email)
                ->send(new OrderCreatedMail($order));
        } catch (\Exception $e) {
            Log::error('Failed to send order created email', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
        
        // Send SMS if phone available
        if ($order->customer->phone) {
            $this->sendSMS($order);
        }
        
        // Log event
        Log::info('Order created', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'customer_id' => $order->customer_id,
            'total' => $order->total
        ]);
    }
    
    private function sendSMS(Order $order)
    {
        // Integration with SMS provider
        // SMS::send($order->customer->phone, "Your order #{$order->order_number} has been created...");
    }
}
```

### **FireWebhook**

```php
<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FireWebhook
{
    public function handle($event)
    {
        $webhookUrl = $this->getWebhookUrl($event);
        
        if (!$webhookUrl) {
            return;
        }
        
        try {
            $payload = method_exists($event, 'toWebhookPayload')
                ? $event->toWebhookPayload()
                : ['event' => class_basename($event)];
            
            $response = Http::timeout(5)
                ->retry(3, 100)
                ->post($webhookUrl, $payload);
            
            if ($response->failed()) {
                Log::warning('Webhook failed', [
                    'event' => class_basename($event),
                    'url' => $webhookUrl,
                    'status' => $response->status()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Webhook exception', [
                'event' => class_basename($event),
                'error' => $e->getMessage()
            ]);
        }
    }
    
    private function getWebhookUrl($event): ?string
    {
        $eventName = class_basename($event);
        $configKey = 'webhooks.' . strtolower($eventName);
        
        return config($configKey);
    }
}
```

---

## 🔄 REGISTER EVENTS

### **EventServiceProvider**

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\{OrderCreated, OrderCompleted, ReturnApproved, InvoiceGenerated};
use App\Listeners\{SendOrderCreatedNotification, FireWebhook};

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Order events
        OrderCreated::class => [
            SendOrderCreatedNotification::class,
            FireWebhook::class,
        ],
        OrderCompleted::class => [
            SendOrderCompletedNotification::class,
            FireWebhook::class,
        ],
        
        // Return events
        ReturnApproved::class => [
            SendReturnApprovedNotification::class,
            FireWebhook::class,
        ],
        
        // Invoice events
        InvoiceGenerated::class => [
            SendInvoiceEmail::class,
            FireWebhook::class,
        ],
    ];

    public function boot()
    {
        //
    }
}
```

---

## 🔔 FIRE EVENTS IN SERVICES

### **In OrderService**

```php
public function create(array $data): Order
{
    $order = DB::transaction(function () use ($data) {
        // Create order logic...
        $order = Order::create([...]);
        
        // Create order items...
        
        return $order;
    });
    
    // Fire event AFTER transaction committed
    event(new OrderCreated($order));
    
    return $order;
}
```

### **In OrderStatusService**

```php
public function updateStatus(int $orderId, string $newStatus): Order
{
    $order = DB::transaction(function () use ($orderId, $newStatus) {
        // Update status logic...
        return $order;
    });
    
    // Fire corresponding event
    match($newStatus) {
        'confirmed' => event(new OrderConfirmed($order)),
        'processing' => event(new OrderProcessing($order)),
        'shipped' => event(new OrderShipped($order)),
        'delivered' => event(new OrderDelivered($order)),
        'completed' => event(new OrderCompleted($order)),
        'cancelled' => event(new OrderCancelled($order)),
        default => null
    };
    
    return $order;
}
```

---

## 📧 EMAIL NOTIFICATIONS

### **OrderCreatedMail**

```php
<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function build()
    {
        return $this->subject("Đơn hàng #{$this->order->order_number} đã được tạo")
            ->view('emails.orders.created')
            ->with([
                'order' => $this->order->load(['items', 'customer'])
            ]);
    }
}
```

---

## ⚙️ CONFIGURATION

### **config/webhooks.php**

```php
<?php

return [
    // Order webhooks
    'ordercreated' => env('WEBHOOK_ORDER_CREATED'),
    'ordercompleted' => env('WEBHOOK_ORDER_COMPLETED'),
    'ordercancelled' => env('WEBHOOK_ORDER_CANCELLED'),
    
    // Return webhooks
    'returnapproved' => env('WEBHOOK_RETURN_APPROVED'),
    'returncompleted' => env('WEBHOOK_RETURN_COMPLETED'),
    
    // Invoice webhooks
    'invoicegenerated' => env('WEBHOOK_INVOICE_GENERATED'),
    
    // Global settings
    'timeout' => 5, // seconds
    'retry_times' => 3,
    'retry_delay' => 100, // milliseconds
];
```

### **.env**

```bash
# Webhook URLs
WEBHOOK_ORDER_CREATED=https://example.com/webhooks/order-created
WEBHOOK_ORDER_COMPLETED=https://example.com/webhooks/order-completed
WEBHOOK_RETURN_APPROVED=https://example.com/webhooks/return-approved
```

---

## 🧪 TESTING

### **Event Tests**

```php
<?php

namespace Tests\Feature\Events;

use Tests\TestCase;
use App\Events\OrderCreated;
use App\Listeners\SendOrderCreatedNotification;
use App\Models\Order;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderEventsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_fires_order_created_event()
    {
        Event::fake([OrderCreated::class]);
        
        $order = $this->orderService->create([...]);
        
        Event::assertDispatched(OrderCreated::class, function ($e) use ($order) {
            return $e->order->id === $order->id;
        });
    }

    /** @test */
    public function it_sends_email_when_order_created()
    {
        Mail::fake();
        
        $order = Order::factory()->create();
        
        event(new OrderCreated($order));
        
        Mail::assertSent(OrderCreatedMail::class, function ($mail) use ($order) {
            return $mail->order->id === $order->id;
        });
    }
}
```

### **Webhook Tests**

```php
<?php

namespace Tests\Feature\Webhooks;

use Tests\TestCase;
use App\Events\OrderCreated;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;

class WebhookTest extends TestCase
{
    /** @test */
    public function it_fires_webhook_on_order_created()
    {
        Http::fake();
        
        config(['webhooks.ordercreated' => 'https://example.com/webhook']);
        
        $order = Order::factory()->create();
        
        event(new OrderCreated($order));
        
        Http::assertSent(function ($request) use ($order) {
            return $request->url() === 'https://example.com/webhook'
                && $request['data']['order_id'] === $order->id;
        });
    }
}
```

---

## 📝 ACCEPTANCE CRITERIA

- [x]  All event classes defined
- [x]  Listeners implemented
- [x]  Webhooks firing correctly
- [x]  Email notifications sent
- [x]  Events fired after transactions
- [x]  Error handling for failed webhooks
- [x]  Retry logic implemented
- [x]  Configuration flexible
- [x]  All tests passing

---

## 🔗 RELATED DOCUMENTS

- [Laravel Events Documentation](https://laravel.com/docs/events)
- [Webhook Best Practices](https://webhooks.fyi/)
- [**ORDER_](https://www.notion.so/SHIPPING_FLOW-SHIPPING-Orders-Workflow-c9c2807fa20e46d99712079779edf072?pvs=21)[FLOW.md](http://FLOW.md)**
- [**RETURN_](https://www.notion.so/RETURN_FLOW-Return-Orders-Workflow-7f5c5a4af4054c22a265d011c56b10e3?pvs=21)[FLOW.md](http://FLOW.md)**