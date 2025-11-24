# Inventory Edge Cases

**Module:** Order Workflow

**Last Updated:** 2025-11-24

---

## 🎯 MỤC ĐÍCH

Document này phân tích **các edge cases về inventory management** trong Order Workflow.

---

## 📉 NEGATIVE STOCK

### **Problem: Overselling**

**Scenario:**

```
Current stock: 5 units
Order A: 3 units → Stock: 2 units ✅
Order B: 4 units → Stock: -2 units ❌
```

**Cause:** Race condition (concurrent orders)

---

### **Solution 1: Hard Limit (Recommended)**

**Schema:**

```sql
ALTER TABLE inventory ADD CONSTRAINT chk_quantity_nonnegative 
    CHECK (quantity >= 0);
```

**Implementation:**

```php
public function deductInventory($productId, $variantId, $branchId, $quantity) {
    DB::transaction(function() use ($productId, $variantId, $branchId, $quantity) {
        $inventory = Inventory::where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('branch_id', $branchId)
            ->lockForUpdate()
            ->first();
        
        if (!$inventory || $inventory->quantity < $quantity) {
            throw new InsufficientStockException();
        }
        
        $inventory->decrement('quantity', $quantity);
    });
}
```

---

### **Solution 2: Soft Limit (Allow Negative)**

**Use Case:** Pre-orders, backorders

**Implementation:**

```php
public function deductInventory($productId, $variantId, $branchId, $quantity, $allowNegative = false) {
    DB::transaction(function() use ($productId, $variantId, $branchId, $quantity, $allowNegative) {
        $inventory = Inventory::where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('branch_id', $branchId)
            ->lockForUpdate()
            ->first();
        
        if (!$allowNegative && $inventory->quantity < $quantity) {
            throw new InsufficientStockException();
        }
        
        // Allow negative stock
        $inventory->decrement('quantity', $quantity);
        
        // Flag for replenishment
        if ($inventory->quantity < 0) {
            logger()->warning("Negative stock: Product {$productId}, Qty: {$inventory->quantity}");
            $this->sendReplenishmentAlert($inventory);
        }
    });
}
```

---

## 🔄 INVENTORY DEDUCTION TIMING

### **Problem: When to Deduct?**

**Option 1: Deduct When Order Created (draft)**

- ❌ Risk: Customers abandon carts
- ❌ Stock locked unnecessarily

**Option 2: Deduct When Order Confirmed**

- ❌ Risk: Stock runs out between create and confirm

**Option 3: Deduct When Processing (Recommended)**

- ✅ Balanced approach
- ✅ Stock locked when actually preparing order

---

### **Implementation**

```php
public function updateOrderStatus($orderId, $newStatus) {
    DB::transaction(function() use ($orderId, $newStatus) {
        $order = Order::where('id', $orderId)
            ->lockForUpdate()
            ->first();
        
        $oldStatus = $order->status;
        
        // Deduct when entering processing
        if ($newStatus === 'processing' && $oldStatus !== 'processing') {
            foreach ($order->items as $item) {
                $this->deductInventory(
                    $item->product_id,
                    $item->variant_id,
                    $order->branch_id,
                    $item->quantity
                );
                
                // Log movement
                InventoryMovement::create([
                    'branch_id' => $order->branch_id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'type' => 'sale',
                    'quantity' => -$item->quantity,
                    'reference_type' => 'order',
                    'reference_id' => $order->id,
                    'created_by' => auth()->id()
                ]);
            }
        }
        
        // Restore if cancelled
        if ($newStatus === 'cancelled' && in_array($oldStatus, ['processing', 'shipping'])) {
            foreach ($order->items as $item) {
                Inventory::where('product_id', $item->product_id)
                    ->where('variant_id', $item->variant_id)
                    ->where('branch_id', $order->branch_id)
                    ->increment('quantity', $item->quantity);
                
                // Log movement
                InventoryMovement::create([
                    'branch_id' => $order->branch_id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'type' => 'adjustment',
                    'quantity' => +$item->quantity,
                    'reference_type' => 'order',
                    'reference_id' => $order->id,
                    'notes' => 'Restored from cancelled order',
                    'created_by' => auth()->id()
                ]);
            }
        }
        
        $order->update(['status' => $newStatus]);
    });
}
```

---

## 🔙 RETURN RESTOCKING

### **Problem: When to Restock?**

**Option 1: Restock When Return Approved**

- ❌ Risk: Items not yet received
- ❌ Customer might not ship back

**Option 2: Restock When Return Completed (Recommended)**

- ✅ Items actually received
- ✅ Quality checked

---

### **Implementation**

```php
public function completeReturn($returnId) {
    DB::transaction(function() use ($returnId) {
        $return = Return::where('id', $returnId)
            ->lockForUpdate()
            ->first();
        
        if ($return->status !== 'approved') {
            throw new ValidationException('Return must be approved first');
        }
        
        // Restock ALL items (regardless of condition)
        foreach ($return->returnItems as $item) {
            $orderItem = $item->orderItem;
            
            Inventory::where('product_id', $orderItem->product_id)
                ->where('variant_id', $orderItem->variant_id)
                ->where('branch_id', $return->order->branch_id)
                ->increment('quantity', $item->quantity_returned);
            
            // Log movement
            InventoryMovement::create([
                'branch_id' => $return->order->branch_id,
                'product_id' => $orderItem->product_id,
                'variant_id' => $orderItem->variant_id,
                'type' => 'return',
                'quantity' => +$item->quantity_returned,
                'reference_type' => 'return',
                'reference_id' => $return->id,
                'created_by' => auth()->id()
            ]);
        }
        
        $return->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
    });
}
```

---

### **Problem: Damaged Items**

**Scenario:**

```
Return 3 items:
- 1x new condition → Restock to main inventory
- 1x used condition → Restock to main inventory
- 1x damaged condition → Scrap or discount inventory?
```

**Phase 1 Solution: Restock Everything**

```php
// Simple: All items restocked regardless of condition
// Track condition for reporting only
```

**Phase 2 Solution: Separate Inventory**

```sql
ALTER TABLE inventory ADD COLUMN condition ENUM('new', 'used', 'damaged');

-- Create separate rows for each condition
INSERT INTO inventory (product_id, variant_id, branch_id, condition, quantity)
VALUES (123, 456, 1, 'new', 10),
       (123, 456, 1, 'used', 2),
       (123, 456, 1, 'damaged', 1);
```

---

## 📊 INVENTORY MOVEMENTS LOGGING

### **Problem: Missing Movement Records**

**Scenario:**

```
Inventory: 100 units
Movements:
- Sale: -50 units
- Return: +10 units
Total: -40 units

Expected stock: 60 units
Actual stock: 55 units ❌

Missing: 5 units (maybe theft, damage, or manual adjustment not logged)
```

---

### **Solution: Audit Trail**

**Query:**

```sql
SELECT 
    product_id,
    variant_id,
    branch_id,
    SUM(quantity) as calculated_stock,
    (
        SELECT quantity 
        FROM inventory 
        WHERE product_id = im.product_id 
          AND variant_id = im.variant_id
          AND branch_id = im.branch_id
    ) as actual_stock,
    SUM(quantity) - (
        SELECT quantity 
        FROM inventory 
        WHERE product_id = im.product_id 
          AND variant_id = im.variant_id
          AND branch_id = im.branch_id
    ) as discrepancy
FROM inventory_movements im
GROUP BY product_id, variant_id, branch_id
HAVING discrepancy != 0;
```

**Fix Discrepancy:**

```php
public function reconcileInventory($productId, $variantId, $branchId) {
    DB::transaction(function() use ($productId, $variantId, $branchId) {
        // Calculate from movements
        $calculated = InventoryMovement::where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('branch_id', $branchId)
            ->sum('quantity');
        
        // Get actual
        $inventory = Inventory::where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('branch_id', $branchId)
            ->first();
        
        $discrepancy = $calculated - $inventory->quantity;
        
        if ($discrepancy !== 0) {
            // Log adjustment
            InventoryMovement::create([
                'branch_id' => $branchId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'type' => 'adjustment',
                'quantity' => $discrepancy,
                'notes' => 'Reconciliation adjustment',
                'created_by' => auth()->id()
            ]);
            
            // Update inventory
            $inventory->update(['quantity' => $calculated]);
        }
    });
}
```

---

## 🏪 MULTI-BRANCH INVENTORY

### **Problem: Transfer Between Branches**

**Scenario:**

```
Branch A: 100 units
Branch B: 5 units

Transfer 20 units from A to B:
Branch A: 80 units
Branch B: 25 units
```

---

### **Implementation**

```php
public function transferInventory($productId, $variantId, $fromBranch, $toBranch, $quantity) {
    DB::transaction(function() use ($productId, $variantId, $fromBranch, $toBranch, $quantity) {
        // Check source stock
        $fromInventory = Inventory::where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('branch_id', $fromBranch)
            ->lockForUpdate()
            ->first();
        
        if ($fromInventory->quantity < $quantity) {
            throw new InsufficientStockException();
        }
        
        // Deduct from source
        $fromInventory->decrement('quantity', $quantity);
        
        // Add to destination
        $toInventory = Inventory::where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('branch_id', $toBranch)
            ->lockForUpdate()
            ->first();
        
        if (!$toInventory) {
            // Create if not exists
            $toInventory = Inventory::create([
                'product_id' => $productId,
                'variant_id' => $variantId,
                'branch_id' => $toBranch,
                'quantity' => 0
            ]);
        }
        
        $toInventory->increment('quantity', $quantity);
        
        // Log movements
        InventoryMovement::create([
            'branch_id' => $fromBranch,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'type' => 'transfer_out',
            'quantity' => -$quantity,
            'reference_type' => 'transfer',
            'notes' => "Transfer to Branch {$toBranch}",
            'created_by' => auth()->id()
        ]);
        
        InventoryMovement::create([
            'branch_id' => $toBranch,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'type' => 'transfer_in',
            'quantity' => +$quantity,
            'reference_type' => 'transfer',
            'notes' => "Transfer from Branch {$fromBranch}",
            'created_by' => auth()->id()
        ]);
    });
}
```

---

## 📦 RESERVED STOCK

### **Problem: Stock Reservation**

**Scenario:**

```
Total stock: 10 units
Order A (draft): 3 units → Reserved
Order B (draft): 5 units → Reserved
Available stock: 2 units

Order C tries to buy 3 units → Should fail ❌
```

---

### **Solution: Track Reserved Quantity**

**Schema:**

```sql
ALTER TABLE inventory ADD COLUMN reserved_quantity INT DEFAULT 0;
```

**Implementation:**

```php
public function reserveStock($orderId) {
    $order = Order::find($orderId);
    
    DB::transaction(function() use ($order) {
        foreach ($order->items as $item) {
            $inventory = Inventory::where('product_id', $item->product_id)
                ->where('variant_id', $item->variant_id)
                ->where('branch_id', $order->branch_id)
                ->lockForUpdate()
                ->first();
            
            $available = $inventory->quantity - $inventory->reserved_quantity;
            
            if ($available < $item->quantity) {
                throw new InsufficientStockException();
            }
            
            // Reserve
            $inventory->increment('reserved_quantity', $item->quantity);
        }
    });
}

public function releaseReservation($orderId) {
    $order = Order::find($orderId);
    
    foreach ($order->items as $item) {
        Inventory::where('product_id', $item->product_id)
            ->where('variant_id', $item->variant_id)
            ->where('branch_id', $order->branch_id)
            ->decrement('reserved_quantity', $item->quantity);
    }
}

public function confirmOrder($orderId) {
    $order = Order::find($orderId);
    
    DB::transaction(function() use ($order) {
        foreach ($order->items as $item) {
            // Deduct from both quantity and reserved
            Inventory::where('product_id', $item->product_id)
                ->where('variant_id', $item->variant_id)
                ->where('branch_id', $order->branch_id)
                ->update([
                    'quantity' => DB::raw('quantity - ' . $item->quantity),
                    'reserved_quantity' => DB::raw('reserved_quantity - ' . $item->quantity)
                ]);
        }
        
        $order->update(['status' => 'confirmed']);
    });
}
```

---

## 🔗 RELATED DOCUMENTS

- [**CONCURRENCY.md**](http://CONCURRENCY.md) - Concurrency edge cases
- [**DATA_](https://www.notion.so/DATA_INTEGRITY-Data-Integrity-Edge-Cases-a99dccea34224a7d8470b6c28c5e458d?pvs=21)[INTEGRITY.md](http://INTEGRITY.md)** - Data integrity edge cases
- **[[FINANCIAL.md](http://FINANCIAL.md)]** - Financial edge cases