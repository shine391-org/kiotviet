# TASK-001: InventoryModule

**Type:** New Module Development
**Priority:** 🔴 CRITICAL
**Effort:** 5 days
**Status:** 📋 PENDING
**Dependencies:** REFACTOR-001 (ProductService - completed)
**Assigned:** AI Agent

---

## 🎯 OBJECTIVE

Xây dựng module quản lý kho hàng đầy đủ cho hệ thống LANO CRM.

**Scope:**
- Multi-warehouse support
- Stock movements tracking (IN/OUT/TRANSFER)
- Real-time stock levels
- Stock alerts & notifications
- Inventory valuation (FIFO/LIFO/Average Cost)
- Stock adjustments & audits
- Integration với Products module

---

## 📊 BUSINESS REQUIREMENTS

### Core Features:

**1. Warehouse Management**
- Quản lý nhiều kho (chi nhánh, kho tổng, kho phụ)
- Mỗi kho có địa chỉ, người quản lý
- Active/Inactive status

**2. Stock Levels**
- Track tồn kho theo product × warehouse
- Real-time updates
- Available vs Reserved vs On-hand quantities
- Safety stock levels (minimum stock)
- Reorder points

**3. Stock Movements**
- **IN:** Nhập kho (from suppliers, transfers, returns)
- **OUT:** Xuất kho (sales, transfers, waste)
- **TRANSFER:** Chuyển kho giữa các warehouses
- **ADJUSTMENT:** Điều chỉnh (kiểm kê, sửa lỗi)

**4. Inventory Valuation**
- Support 3 methods: FIFO, LIFO, Average Cost
- Track cost per movement
- Calculate inventory value reports

**5. Alerts & Notifications**
- Low stock alerts (below minimum)
- Out of stock alerts
- Expiring products (if applicable)
- Overstock warnings

**6. Audits & History**
- Full history of stock movements
- Who did what when
- Reconciliation support

---

## 🗄️ DATABASE SCHEMA

### Tables to CREATE:

**1. warehouses**
CREATE TABLE warehouses (
id INT PRIMARY KEY AUTO_INCREMENT,
code VARCHAR(50) UNIQUE NOT NULL,
name VARCHAR(255) NOT NULL,
address TEXT,
phone VARCHAR(20),
manager_id INT,
status ENUM('active', 'inactive') DEFAULT 'active',
is_default BOOLEAN DEFAULT FALSE,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
deleted_at TIMESTAMP NULL,

INDEX idx_code (code),
INDEX idx_status (status),
FOREIGN KEY (manager_id) REFERENCES users(id)
);


**2. inventory_stock**
CREATE TABLE inventory_stock (
id INT PRIMARY KEY AUTO_INCREMENT,
product_id INT NOT NULL,
variant_id INT NULL,
warehouse_id INT NOT NULL,
quantity_on_hand DECIMAL(10,2) DEFAULT 0,
quantity_reserved DECIMAL(10,2) DEFAULT 0,
quantity_available DECIMAL(10,2) AS (quantity_on_hand - quantity_reserved) STORED,
minimum_stock DECIMAL(10,2) DEFAULT 0,
reorder_point DECIMAL(10,2) DEFAULT 0,
last_movement_at TIMESTAMP NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

UNIQUE KEY unique_stock (product_id, variant_id, warehouse_id),
INDEX idx_product (product_id),
INDEX idx_warehouse (warehouse_id),
INDEX idx_low_stock (quantity_available, minimum_stock),
FOREIGN KEY (product_id) REFERENCES products(id),
FOREIGN KEY (variant_id) REFERENCES product_variants_v2(id),
FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
);


**3. inventory_movements**
CREATE TABLE inventory_movements (
id INT PRIMARY KEY AUTO_INCREMENT,
reference_code VARCHAR(50) UNIQUE NOT NULL,
movement_type ENUM('IN', 'OUT', 'TRANSFER', 'ADJUSTMENT') NOT NULL,
product_id INT NOT NULL,
variant_id INT NULL,
from_warehouse_id INT NULL,
to_warehouse_id INT NULL,
quantity DECIMAL(10,2) NOT NULL,
unit_cost DECIMAL(10,2) DEFAULT 0,
total_cost DECIMAL(10,2) AS (quantity * unit_cost) STORED,
reason VARCHAR(255),
reference_type VARCHAR(50), -- 'purchase_order', 'sale_order', 'transfer', 'adjustment'
reference_id INT,
notes TEXT,
created_by INT NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

INDEX idx_reference (reference_code),
INDEX idx_type (movement_type),
INDEX idx_product (product_id),
INDEX idx_warehouse_from (from_warehouse_id),
INDEX idx_warehouse_to (to_warehouse_id),
INDEX idx_date (created_at),
FOREIGN KEY (product_id) REFERENCES products(id),
FOREIGN KEY (variant_id) REFERENCES product_variants_v2(id),
FOREIGN KEY (from_warehouse_id) REFERENCES warehouses(id),
FOREIGN KEY (to_warehouse_id) REFERENCES warehouses(id),
FOREIGN KEY (created_by) REFERENCES users(id)
);


**4. inventory_valuation**
CREATE TABLE inventory_valuation (
id INT PRIMARY KEY AUTO_INCREMENT,
warehouse_id INT NOT NULL,
product_id INT NOT NULL,
variant_id INT NULL,
valuation_method ENUM('FIFO', 'LIFO', 'AVERAGE') NOT NULL,
quantity DECIMAL(10,2) NOT NULL,
unit_cost DECIMAL(10,2) NOT NULL,
total_value DECIMAL(10,2) AS (quantity * unit_cost) STORED,
movement_id INT,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

INDEX idx_warehouse (warehouse_id),
INDEX idx_product (product_id),
INDEX idx_method (valuation_method),
FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
FOREIGN KEY (product_id) REFERENCES products(id),
FOREIGN KEY (variant_id) REFERENCES product_variants_v2(id),
FOREIGN KEY (movement_id) REFERENCES inventory_movements(id)
);


**5. inventory_alerts**
CREATE TABLE inventory_alerts (
id INT PRIMARY KEY AUTO_INCREMENT,
alert_type ENUM('LOW_STOCK', 'OUT_OF_STOCK', 'OVERSTOCK', 'EXPIRING') NOT NULL,
product_id INT NOT NULL,
variant_id INT NULL,
warehouse_id INT NOT NULL,
current_quantity DECIMAL(10,2),
threshold_quantity DECIMAL(10,2),
status ENUM('active', 'resolved', 'ignored') DEFAULT 'active',
resolved_at TIMESTAMP NULL,
resolved_by INT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

INDEX idx_type (alert_type),
INDEX idx_status (status),
INDEX idx_product (product_id),
INDEX idx_warehouse (warehouse_id),
FOREIGN KEY (product_id) REFERENCES products(id),
FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
);


---

## 🏗️ ARCHITECTURE

### Files to CREATE:

**1. Models (Passive, schema only):**
- `app/Models/WarehouseModel.php`
- `app/Models/InventoryStockModel.php`
- `app/Models/InventoryMovementModel.php`
- `app/Models/InventoryValuationModel.php`
- `app/Models/InventoryAlertModel.php`

**2. Validators:**
- `app/Validators/WarehouseValidator.php`
- `app/Validators/InventoryValidator.php`

**3. Repositories:**
- `app/Repositories/Inventory/WarehouseRepository.php`
- `app/Repositories/Inventory/StockRepository.php`
- `app/Repositories/Inventory/MovementRepository.php`
- `app/Repositories/Inventory/ValuationRepository.php`
- `app/Repositories/Inventory/AlertRepository.php`

**4. Services:**
- `app/Services/Inventory/WarehouseService.php`
- `app/Services/Inventory/StockService.php`
- `app/Services/Inventory/MovementService.php`
- `app/Services/Inventory/ValuationService.php`
- `app/Services/Inventory/AlertService.php`

**5. Controllers:**
- `app/Controllers/Api/WarehousesController.php`
- `app/Controllers/Api/InventoryController.php`
- `app/Controllers/Api/StockMovementsController.php`

**6. Migrations:**
- `app/Database/Migrations/YYYY-MM-DD-create-warehouses-table.php`
- `app/Database/Migrations/YYYY-MM-DD-create-inventory-stock-table.php`
- `app/Database/Migrations/YYYY-MM-DD-create-inventory-movements-table.php`
- `app/Database/Migrations/YYYY-MM-DD-create-inventory-valuation-table.php`
- `app/Database/Migrations/YYYY-MM-DD-create-inventory-alerts-table.php`

**7. Tests:**
- `tests/Services/Inventory/WarehouseServiceTest.php`
- `tests/Services/Inventory/StockServiceTest.php`
- `tests/Services/Inventory/MovementServiceTest.php`
- `tests/Repositories/Inventory/StockRepositoryTest.php`

---

## 📋 DETAILED REQUIREMENTS

### 1. WarehouseService

**Responsibilities:**
- Warehouse CRUD
- Set default warehouse
- Activate/deactivate warehouses

**Methods:**
public function list(array $filters): array
public function get(int $id): array
public function create(array $data): array
public function update(int $id, array $data): array
public function delete(int $id): array
public function setDefault(int $id): array
public function activate(int $id): array
public function deactivate(int $id): array

text

---

### 2. StockService

**Responsibilities:**
- Get stock levels by product/warehouse
- Check availability
- Reserve stock (for orders)
- Release stock (cancel orders)
- Set minimum stock levels

**Methods:**
public function getByProduct(int $productId): array // All warehouses
public function getByWarehouse(int $warehouseId): array // All products
public function getStock(int $productId, int $warehouseId, ?int $variantId = null): array
public function checkAvailability(int $productId, int $warehouseId, float $quantity): bool
public function reserveStock(int $productId, int $warehouseId, float $quantity, string $reference): array
public function releaseStock(int $productId, int $warehouseId, float $quantity, string $reference): array
public function setMinimumStock(int $productId, int $warehouseId, float $quantity): array
public function getLowStockItems(int $warehouseId): array


---

### 3. MovementService

**Responsibilities:**
- Record stock movements (IN/OUT/TRANSFER/ADJUSTMENT)
- Generate reference codes
- Update stock levels
- Trigger valuation updates
- Trigger alerts

**Methods:**
public function list(array $filters): array
public function get(int $id): array
public function recordIn(array $data): array // Nhập kho
public function recordOut(array $data): array // Xuất kho
public function recordTransfer(array $data): array // Chuyển kho
public function recordAdjustment(array $data): array // Điều chỉnh
public function getHistory(int $productId, ?int $warehouseId = null): array
public function generateReferenceCode(string $type): string // Auto generate: IN-20251121-001


**Business Logic - recordIn():**
Validate input

Generate reference code

Create movement record (type=IN)

Update inventory_stock (increase quantity_on_hand)

Create valuation record (with unit_cost)

Check và clear alerts (if low stock resolved)

Return success với updated stock


**Business Logic - recordOut():**
Validate input

Check availability (quantity_available >= requested)

Generate reference code

Create movement record (type=OUT)

Update inventory_stock (decrease quantity_on_hand)

Update valuation (remove cost based on method: FIFO/LIFO/AVG)

Check và create alerts (if low stock triggered)

Return success

**Business Logic - recordTransfer():**
Validate input

Check availability in FROM warehouse

Generate reference code

Create movement record (type=TRANSFER, from_warehouse, to_warehouse)

Update FROM warehouse (decrease)

Update TO warehouse (increase)

Update valuations for both warehouses

Check alerts for both

Return success


---

### 4. ValuationService

**Responsibilities:**
- Calculate inventory value
- Support FIFO/LIFO/Average methods
- Generate valuation reports

**Methods:**
public function calculateValue(int $warehouseId, string $method = 'AVERAGE'): array
public function getValuationReport(int $warehouseId): array
public function updateValuation(int $movementId): void // After movement
public function getProductCost(int $productId, int $warehouseId, string $method): float


**FIFO Logic:**
Oldest stock (first in) used first for OUT movements
Cost = Cost of oldest batch


**LIFO Logic:**
Newest stock (last in) used first for OUT movements
Cost = Cost of newest batch


**Average Cost Logic:**
Cost = Total value / Total quantity
Updated after every IN movement


---

### 5. AlertService

**Responsibilities:**
- Check stock levels
- Generate alerts
- Resolve/ignore alerts
- Get active alerts

**Methods:**
public function checkStockLevels(): array // Run periodically (cron)
public function getActiveAlerts(?int $warehouseId = null): array
public function resolveAlert(int $alertId, int $userId): array
public function ignoreAlert(int $alertId, int $userId): array
public function createAlert(string $type, array $data): array


---

## 🔌 API ENDPOINTS

### Warehouses:
GET /api/warehouses → List warehouses
GET /api/warehouses/{id} → Get warehouse
POST /api/warehouses → Create warehouse
PUT /api/warehouses/{id} → Update warehouse
DELETE /api/warehouses/{id} → Delete warehouse
PUT /api/warehouses/{id}/set-default → Set as default
PUT /api/warehouses/{id}/activate → Activate
PUT /api/warehouses/{id}/deactivate → Deactivate


### Inventory Stock:
GET /api/inventory/stock → List all stock
GET /api/inventory/stock/product/{productId} → Stock by product
GET /api/inventory/stock/warehouse/{warehouseId} → Stock by warehouse
GET /api/inventory/stock/{productId}/{warehouseId} → Specific stock
POST /api/inventory/stock/reserve → Reserve stock
POST /api/inventory/stock/release → Release stock
PUT /api/inventory/stock/{id}/minimum → Set minimum stock
GET /api/inventory/stock/low-stock → Get low stock items


### Stock Movements:
GET /api/inventory/movements → List movements
GET /api/inventory/movements/{id} → Get movement
POST /api/inventory/movements/in → Record IN
POST /api/inventory/movements/out → Record OUT
POST /api/inventory/movements/transfer → Record TRANSFER
POST /api/inventory/movements/adjustment → Record ADJUSTMENT
GET /api/inventory/movements/history/{productId} → Movement history


### Valuation:
GET /api/inventory/valuation/{warehouseId} → Get valuation report
GET /api/inventory/valuation/product/{productId} → Product valuation


### Alerts:
GET /api/inventory/alerts → Get active alerts
GET /api/inventory/alerts/{id} → Get alert details
PUT /api/inventory/alerts/{id}/resolve → Resolve alert
PUT /api/inventory/alerts/{id}/ignore → Ignore alert
POST /api/inventory/alerts/check → Manual check (trigger)


---

## 🧪 TESTING REQUIREMENTS

### Unit Tests (Services):

**WarehouseServiceTest:**
- [ ] testListWarehouses()
- [ ] testCreateWarehouse()
- [ ] testSetDefaultWarehouse()
- [ ] testDeactivateWarehouse()

**StockServiceTest:**
- [ ] testGetStockByProduct()
- [ ] testCheckAvailability()
- [ ] testReserveStock()
- [ ] testReleaseStock()
- [ ] testGetLowStockItems()

**MovementServiceTest:**
- [ ] testRecordIn()
- [ ] testRecordOut()
- [ ] testRecordOutInsufficientStock()
- [ ] testRecordTransfer()
- [ ] testRecordAdjustment()
- [ ] testGenerateReferenceCode()

**ValuationServiceTest:**
- [ ] testCalculateValueFIFO()
- [ ] testCalculateValueLIFO()
- [ ] testCalculateValueAverage()

**Target:** 30+ tests, 80% coverage

---

## ✅ ACCEPTANCE CRITERIA

### Functional:
- [ ] Can create and manage warehouses
- [ ] Stock levels update correctly on movements
- [ ] IN/OUT/TRANSFER movements work
- [ ] Adjustments work correctly
- [ ] FIFO/LIFO/Average valuation accurate
- [ ] Low stock alerts trigger correctly
- [ ] Reserve/Release stock for orders
- [ ] Movement history complete and accurate

### Technical:
- [ ] All migrations run successfully
- [ ] All models created
- [ ] All validators work
- [ ] All repositories query correctly
- [ ] All services have business logic
- [ ] All controllers thin (routing only)
- [ ] All endpoints return correct format
- [ ] Inline docs với @agent- tags
- [ ] Tests pass 100%
- [ ] Pre-commit checks pass

### Integration:
- [ ] Products module integration works
- [ ] Can query product stock levels
- [ ] Stock updates when creating movements
- [ ] Alerts visible in frontend

---

## 🔗 INTEGRATION POINTS

### With Products Module:
// ProductService.php - Add stock check
public function create(array $data): array
{
// Create product
$product = $this->repo->create($data);

text
// Initialize stock in default warehouse
$inventoryService = new StockService();
$inventoryService->initializeStock($product['id'], $data['initial_stock'] ?? 0);

return $product;
}


### With Orders Module (Future):
// OrderService.php - Reserve stock when order created
public function create(array $data): array
{
// Validate order
// ...

// Reserve stock
$stockService = new StockService();
foreach ($data['items'] as $item) {
    $stockService->reserveStock(
        $item['product_id'],
        $data['warehouse_id'],
        $item['quantity'],
        'ORDER-' . $orderId
    );
}
}


---

## 🚀 IMPLEMENTATION PLAN

### Day 1: Database & Models
- [ ] Create all migrations
- [ ] Create all models
- [ ] Run migrations
- [ ] Seed sample warehouses

### Day 2: Validators & Repositories
- [ ] Create validators
- [ ] Create repositories
- [ ] Write repository tests
- [ ] Test DB queries

### Day 3: Services (Core)
- [ ] WarehouseService
- [ ] StockService
- [ ] Write service tests

### Day 4: Services (Advanced)
- [ ] MovementService
- [ ] ValuationService
- [ ] AlertService
- [ ] Write service tests

### Day 5: Controllers & Integration
- [ ] Create controllers
- [ ] Test all endpoints
- [ ] Integration với Products
- [ ] Final testing
- [ ] Documentation

---

## 📚 REFERENCES

**Pattern to follow:**
- REFACTOR-001: ProductService (architecture pattern)
- AGENTS.md (clean architecture guidelines)
- .ai/instructions.md (coding standards)

**Database design references:**
- ERPNext Inventory Module
- Odoo Stock Management
- Standard warehouse management patterns

---

## 🎯 SUCCESS METRICS

- [ ] 5 database tables created
- [ ] 15+ API endpoints working
- [ ] 30+ tests passing
- [ ] Can track stock across multiple warehouses
- [ ] Stock movements logged completely
- [ ] Alerts triggering correctly
- [ ] Ready for Orders module integration

---

## 📝 DELIVERABLES

- [ ] 5 migrations
- [ ] 5 models
- [ ] 2 validators
- [ ] 5 repositories
- [ ] 5 services
- [ ] 3 controllers
- [ ] 30+ tests
- [ ] Session log
- [ ] API documentation
- [ ] Integration guide

---

## 🚨 IMPORTANT NOTES

1. **Stock Safety:** Always check availability before OUT/TRANSFER
2. **Atomicity:** Use DB transactions for movements (update stock + create movement together)
3. **Soft Delete:** Warehouses soft delete only (keep historical data)
4. **Reference Codes:** Must be unique and sequential
5. **Valuation:** Update after EVERY movement
6. **Alerts:** Run check periodically (cron job)
7. **Performance:** Index heavily used columns (product_id, warehouse_id, created_at)

---

**Created:** 2025-11-21
**Status:** 📋 PENDING
**Estimated Start:** After REFACTOR-002, 003 (or now if skipping)
**Estimated Completion:** 5 days
**Priority:** 🔴 CRITICAL (blocks Orders, POS, Finance modules)