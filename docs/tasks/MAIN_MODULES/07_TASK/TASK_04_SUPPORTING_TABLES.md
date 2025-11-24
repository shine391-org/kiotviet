# TASK_04: Supporting Tables Implementation

**Priority:** P1 (High)

**Estimated Effort:** 2 days

**Dependencies:** None

**Status:** Ready

---

## 🎯 OBJECTIVE

Implement **3 supporting tables**: order_status_logs, inventory_movements, branches.

---

## 📋 REQUIREMENTS

### **Functional Requirements**

- [x]  Create order_status_logs table
- [x]  Create inventory_movements table
- [x]  Create branches table
- [x]  Auto-log status changes
- [x]  Track all inventory movements
- [x]  Support multi-branch operations

---

## 🗄️ DATABASE SCHEMAS

See [**SUPPORTING_](https://www.notion.so/SUPPORTING_TABLES-Supporting-Tables-Schema-88f443621e234de7a42f404439a5ac57?pvs=21)[TABLES.md](http://TABLES.md)** for full details.

### **1. branches**

```sql
CREATE TABLE branches (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    ward VARCHAR(100),
    district VARCHAR(100),
    city VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_active (is_active)
);
```

---

### **2. order_status_logs**

```sql
CREATE TABLE order_status_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    from_status VARCHAR(50),
    to_status VARCHAR(50) NOT NULL,
    notes TEXT,
    changed_by BIGINT UNSIGNED NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order (order_id, changed_at)
);
```

---

### **3. inventory_movements**

```sql
CREATE TABLE inventory_movements (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    branch_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    variant_id BIGINT UNSIGNED,
    type ENUM('sale', 'return', 'adjustment', 'transfer_in', 'transfer_out') NOT NULL,
    quantity INT NOT NULL,
    reference_type VARCHAR(50),
    reference_id BIGINT UNSIGNED,
    notes TEXT,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_branch_product (branch_id, product_id, created_at)
);
```

---

## 🏗️ MODELS

### **OrderStatusLog Model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusLog extends Model
{
    const UPDATED_AT = null; // Only created_at
    
    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'notes',
        'changed_by',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
```

---

### **InventoryMovement Model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    const UPDATED_AT = null; // Only created_at
    
    protected $fillable = [
        'branch_id',
        'product_id',
        'variant_id',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

---

## 🔍 AUTO-LOGGING STATUS CHANGES

### **Observer Pattern**

```php
<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\OrderStatusLog;

class OrderObserver
{
    public function updating(Order $order)
    {
        // Check if status changed
        if ($order->isDirty('status')) {
            OrderStatusLog::create([
                'order_id' => $order->id,
                'from_status' => $order->getOriginal('status'),
                'to_status' => $order->status,
                'changed_by' => auth()->id(),
            ]);
        }
    }
}

// Register in AppServiceProvider
public function boot()
{
    Order::observe(OrderObserver::class);
}
```

---

## 📦 INVENTORY MOVEMENT TRACKING

### **Service Class**

```php
<?php

namespace App\Services;

use App\Models\InventoryMovement;

class InventoryMovementLogger
{
    public function log(
        int $branchId,
        int $productId,
        ?int $variantId,
        string $type,
        int $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): InventoryMovement {
        return InventoryMovement::create([
            'branch_id' => $branchId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'type' => $type,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_by' => auth()->id(),
        ]);
    }
}
```

---

## 🧪 TESTING

### **Test: Status Logging**

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\OrderStatusLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderStatusLoggingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_logs_status_changes_automatically()
    {
        $order = Order::factory()->create(['status' => 'draft']);
        
        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'to_status' => 'draft'
        ]);
        
        $order->update(['status' => 'confirmed']);
        
        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'from_status' => 'draft',
            'to_status' => 'confirmed'
        ]);
    }
}
```

---

## 📝 ACCEPTANCE CRITERIA

- [x]  All 3 tables created
- [x]  Status changes auto-logged
- [x]  Inventory movements tracked
- [x]  Branch operations supported
- [x]  Cascade deletes configured correctly
- [x]  All tests passing

---

## 🔗 RELATED DOCUMENTS

- [**SUPPORTING_](https://www.notion.so/SUPPORTING_TABLES-Supporting-Tables-Schema-88f443621e234de7a42f404439a5ac57?pvs=21)[TABLES.md](http://TABLES.md)** - Schema details
- [**INVENTORY.md**](http://INVENTORY.md) - Inventory edge cases