# Kế Hoạch Implementation Tính Năng ERPNext cho LanoCRM
## Product Management và Order Management Enhancement

---

### 1. Tổng quan

Dựa trên phân tích so sánh giữa ERPNext và LanoCRM, tài liệu này outlines kế hoạch implementation chi tiết cho các tính năng còn thiếu để LanoCRM có thể cạnh tranh với ERPNext.

**Mục tiêu:**
- Nâng cấp Product Management từ 80% → 95%
- Nâng cấp Order Management từ 70% → 90%
- Thêm các tính năng enterprise-level
- Giữ nguyên kiến trúc Clean Architecture

---

### 2. Priority 1 - Critical Features (3 tháng tới)

#### 2.1 Batch/Serial Number Tracking

**Mô tả:**
- Theo dõi sản phẩm theo lô sản xuất
- Theo dõi từng sản phẩm bằng số serial
- Quản lý hạn sử dụng và bảo hành

**Files cần tạo:**

**Database Migration:**
```php
// backend-ci/app/Database/Migrations/2025-12-01-000002_CreateProductBatchTables.php
class CreateProductBatchTables extends Migration
{
    public function up()
    {
        // product_batches table
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'batch_number' => ['type' => 'VARCHAR', 'constraint' => 100],
            'manufacturing_date' => ['type' => 'DATE', 'null' => true],
            'expiry_date' => ['type' => 'DATE', 'null' => true],
            'initial_quantity' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'current_quantity' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'cost_per_unit' => ['type' => 'DECIMAL', 'constraint' => '14,4', 'default' => 0],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['product_id', 'batch_number']);
        $this->forge->createTable('product_batches');
        
        // product_serial_numbers table
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'batch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'serial_number' => ['type' => 'VARCHAR', 'constraint' => 100],
            'status' => ['type' => 'ENUM', 'constraint' => ['available', 'sold', 'returned', 'damaged'], 'default' => 'available'],
            'warranty_expiry_date' => ['type' => 'DATE', 'null' => true],
            'sold_to_order_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'sold_date' => ['type' => 'DATE', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['product_id', 'serial_number']);
        $this->forge->createTable('product_serial_numbers');
    }
}
```

**Repository Layer:**
```php
// backend-ci/app/Repositories/Products/ProductBatchRepository.php
class ProductBatchRepository
{
    public function create(array $data): array
    public function findByBatchNumber(string $batchNumber): ?array
    public function findByProduct(int $productId): array
    public function updateQuantity(int $batchId, float $quantity): bool
    public function getExpiringBatches(int $daysAhead): array
}

// backend-ci/app/Repositories/Products/ProductSerialNumberRepository.php
class ProductSerialNumberRepository
{
    public function create(array $data): array
    public function findBySerialNumber(string $serialNumber): ?array
    public function findByProduct(int $productId): array
    public function updateStatus(int $serialId, string $status): bool
    public function reserveSerialNumbers(array $serialIds): bool
}
```

**Service Layer:**
```php
// backend-ci/app/Services/Products/ProductBatchService.php
class ProductBatchService
{
    public function createBatch(array $data): array
    public function updateBatch(int $id, array $data): array
    public function adjustBatchQuantity(int $batchId, float $adjustment): array
    public function getBatchByNumber(string $batchNumber): array
    public function getExpiringBatches(int $daysAhead = 30): array
}

// backend-ci/app/Services/Products/ProductSerialNumberService.php
class ProductSerialNumberService
{
    public function createSerialNumbers(array $data): array
    public function updateSerialNumber(int $id, array $data): array
    public function sellSerialNumber(int $serialId, int $orderId): array
    public function returnSerialNumber(int $serialId): array
    public function getAvailableSerialNumbers(int $productId): array
}
```

**Controller Layer:**
```php
// backend-ci/app/Controllers/Api/ProductBatchesController.php
class ProductBatchesController extends BaseController
{
    public function index() // List batches
    public function create() // Create batch
    public function show($id) // Show batch
    public function update($id) // Update batch
    public function delete($id) // Delete batch
    public function adjustQuantity($id) // Adjust batch quantity
}

// backend-ci/app/Controllers/Api/ProductSerialNumbersController.php
class ProductSerialNumbersController extends BaseController
{
    public function index() // List serial numbers
    public function create() // Create serial numbers
    public function show($id) // Show serial number
    public function update($id) // Update serial number
    public function delete($id) // Delete serial number
    public function reserve() // Reserve serial numbers
}
```

**API Routes:**
```php
// backend-ci/app/Config/Routes.php
$routes->group('api', ['filter' => 'jwt-auth'], function($routes) {
    $routes->resource('product-batches', ['controller' => 'Api\ProductBatchesController']);
    $routes->resource('product-serial-numbers', ['controller' => 'Api\ProductSerialNumbersController']);
    $routes->post('product-batches/(\d+)/adjust-quantity', 'Api\ProductBatchesController::adjustQuantity/$1');
    $routes->post('product-serial-numbers/reserve', 'Api\ProductSerialNumbersController::reserve');
});
```

#### 2.2 Delivery Notes Management

**Mô tả:**
- Tạo phiếu giao hàng từ đơn hàng
- Theo dõi trạng thái giao hàng
- Quản lý batch/serial number khi giao

**Files cần tạo:**

**Database Migration:**
```php
// backend-ci/app/Database/Migrations/2025-12-01-000003_CreateDeliveryNoteTables.php
class CreateDeliveryNoteTables extends Migration
{
    public function up()
    {
        // delivery_notes table
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'delivery_number' => ['type' => 'VARCHAR', 'constraint' => 50],
            'order_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'customer_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'delivery_date' => ['type' => 'DATE'],
            'expected_delivery_date' => ['type' => 'DATE', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'confirmed', 'packed', 'shipped', 'delivered', 'cancelled'], 'default' => 'draft'],
            'shipping_address' => ['type' => 'TEXT', 'null' => true],
            'tracking_number' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'carrier' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true],
            'confirmed_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'confirmed_at' => ['type' => 'DATETIME', 'null' => true],
            'delivered_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'delivered_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('delivery_number');
        $this->forge->addKey('order_id');
        $this->forge->addKey('customer_id');
        $this->forge->createTable('delivery_notes');
        
        // delivery_note_items table
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'delivery_note_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'order_item_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'batch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'serial_number' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'quantity' => ['type' => 'DECIMAL', 'constraint' => '12,3'],
            'delivered_quantity' => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('delivery_note_id');
        $this->forge->addKey('order_item_id');
        $this->forge->createTable('delivery_note_items');
    }
}
```

**Repository Layer:**
```php
// backend-ci/app/Repositories/DeliveryNotes/DeliveryNoteRepository.php
class DeliveryNoteRepository
{
    public function create(array $data): array
    public function createWithItems(array $deliveryData, array $itemsData): array
    public function findByOrder(int $orderId): array
    public function findByNumber(string $deliveryNumber): ?array
    public function updateStatus(int $id, string $status): bool
    public function nextNumber(int $branchId): string
}
```

**Service Layer:**
```php
// backend-ci/app/Services/DeliveryNotes/DeliveryNoteService.php
class DeliveryNoteService
{
    public function createFromOrder(int $orderId): array
    public function confirmDeliveryNote(int $id, int $userId): array
    public function markAsDelivered(int $id, int $userId): array
    public function cancelDeliveryNote(int $id): array
    public function getDeliveryNotesByOrder(int $orderId): array
    public function updateDeliveryItems(int $deliveryNoteId, array $items): array
}
```

**Controller Layer:**
```php
// backend-ci/app/Controllers/Api/DeliveryNotesController.php
class DeliveryNotesController extends BaseController
{
    public function index() // List delivery notes
    public function create() // Create delivery note
    public function show($id) // Show delivery note
    public function update($id) // Update delivery note
    public function confirm($id) // Confirm delivery
    public function deliver($id) // Mark as delivered
    public function cancel($id) // Cancel delivery
    public function createFromOrder() // Create from order
}
```

#### 2.3 Stock Reconciliation

**Mô tả:**
- Kiểm kê tồn kho
- Điều chỉnh sai lệch
- Phê duyệt điều chỉnh

**Files cần tạo:**

**Database Migration:**
```php
// backend-ci/app/Database/Migrations/2025-12-01-000004_CreateStockReconciliationTables.php
class CreateStockReconciliationTables extends Migration
{
    public function up()
    {
        // stock_reconciliations table
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'reconciliation_number' => ['type' => 'VARCHAR', 'constraint' => 50],
            'branch_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'reconciliation_date' => ['type' => 'DATE'],
            'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'submitted', 'approved', 'rejected'], 'default' => 'draft'],
            'total_variance' => ['type' => 'DECIMAL', 'constraint' => '14,4', 'default' => 0],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true],
            'submitted_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'submitted_at' => ['type' => 'DATETIME', 'null' => true],
            'approved_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'approved_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('reconciliation_number');
        $this->forge->createTable('stock_reconciliations');
        
        // stock_reconciliation_items table
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'reconciliation_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'batch_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'system_quantity' => ['type' => 'DECIMAL', 'constraint' => '12,3'],
            'actual_quantity' => ['type' => 'DECIMAL', 'constraint' => '12,3'],
            'variance' => ['type' => 'DECIMAL', 'constraint' => '12,3'],
            'variance_value' => ['type' => 'DECIMAL', 'constraint' => '14,4'],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('reconciliation_id');
        $this->forge->createTable('stock_reconciliation_items');
    }
}
```

#### 2.4 Order Approval Workflow

**Mô tả:**
- Workflow phê duyệt đơn hàng
- Multi-level approval
- Conditional approval rules

**Files cần tạo:**

**Database Migration:**
```php
// backend-ci/app/Database/Migrations/2025-12-01-000005_CreateOrderApprovalTables.php
class CreateOrderApprovalTables extends Migration
{
    public function up()
    {
        // order_approval_rules table
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'condition_type' => ['type' => 'ENUM', 'constraint' => ['amount', 'customer', 'product', 'custom'], 'default' => 'amount'],
            'condition_value' => ['type' => 'TEXT', 'null' => true],
            'approver_role_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('order_approval_rules');
        
        // order_approvals table
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'order_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'approval_rule_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'approver_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'rejected'], 'default' => 'pending'],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'approved_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('order_id');
        $this->forge->createTable('order_approvals');
    }
}
```

---

### 3. Priority 2 - Important Features (6 tháng tới)

#### 3.1 Advanced Pricing System

**Mô tả:**
- Customer-specific pricing
- Project-based pricing
- Historical pricing
- Price change tracking

**Files cần tạo:**
- Database Migration: `CreateCustomerPriceListTables.php`
- Repository: `CustomerPriceListRepository.php`
- Service: `CustomerPriceListService.php`
- Controller: `CustomerPriceListsController.php`

#### 3.2 Reorder Level Planning

**Mô tả:**
- Auto-generate purchase suggestions
- Stock forecasting
- Reorder point calculation

**Files cần tạo:**
- Database Migration: `CreateReorderLevelTables.php`
- Repository: `ReorderLevelRepository.php`
- Service: `ReorderLevelService.php`
- Controller: `ReorderLevelsController.php`

#### 3.3 Quality Management

**Mô tả:**
- Quality inspections
- Quality parameters
- Inspection reports

**Files cần tạo:**
- Database Migration: `CreateQualityInspectionTables.php`
- Repository: `QualityInspectionRepository.php`
- Service: `QualityInspectionService.php`
- Controller: `QualityInspectionsController.php`

#### 3.4 Order Templates

**Mô tả:**
- Order templates for recurring orders
- Quick order creation
- Template management

**Files cần tạo:**
- Database Migration: `CreateOrderTemplateTables.php`
- Repository: `OrderTemplateRepository.php`
- Service: `OrderTemplateService.php`
- Controller: `OrderTemplatesController.php`

---

### 4. Priority 3 - Nice-to-have Features (12 tháng tới)

#### 4.1 Manufacturing Integration

**Mô tả:**
- Bill of Materials (BOM)
- Work Orders
- Production planning

**Files cần tạo:**
- Database Migration: `CreateManufacturingTables.php`
- Repository: `BOMRepository.php`, `WorkOrderRepository.php`
- Service: `BOMService.php`, `WorkOrderService.php`
- Controller: `BOMController.php`, `WorkOrderController.php`

#### 4.2 E-commerce Integration

**Mô tả:**
- Webhooks for e-commerce platforms
- Product sync
- Order sync

**Files cần tạo:**
- Service: `EcommerceIntegrationService.php`
- Controller: `WebhookController.php`
- Middleware: `EcommerceAuthMiddleware.php`

#### 4.3 Subscription Management

**Mô tả:**
- Recurring orders
- Subscription billing
- Automated order generation

**Files cần tạo:**
- Database Migration: `CreateSubscriptionTables.php`
- Repository: `SubscriptionRepository.php`
- Service: `SubscriptionService.php`
- Controller: `SubscriptionsController.php`

---

### 5. Testing Strategy

#### 5.1 Unit Tests

**Batch/Serial Number Tracking:**
```php
// tests/Services/ProductBatchServiceTest.php
class ProductBatchServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use ProductSchemaTrait;
    
    public function testCreateBatch()
    public function testUpdateBatchQuantity()
    public function testGetExpiringBatches()
    public function testBatchNumberUniqueness()
}

// tests/Services/ProductSerialNumberServiceTest.php
class ProductSerialNumberServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use ProductSchemaTrait;
    
    public function testCreateSerialNumbers()
    public function testSellSerialNumber()
    public function testReturnSerialNumber()
    public function testSerialNumberUniqueness()
}
```

**Delivery Notes:**
```php
// tests/Services/DeliveryNoteServiceTest.php
class DeliveryNoteServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use OrderSchemaTrait;
    
    public function testCreateDeliveryNoteFromOrder()
    public function testConfirmDeliveryNote()
    public function testMarkAsDelivered()
    public function testCancelDeliveryNote()
}
```

#### 5.2 Integration Tests

**Batch/Serial Number Integration:**
```php
// tests/Integration/ProductBatchIntegrationTest.php
class ProductBatchIntegrationTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    
    public function testBatchCreationThroughAPI()
    public function testBatchQuantityAdjustment()
    public function testBatchExpiryTracking()
}
```

**Delivery Notes Integration:**
```php
// tests/Integration/DeliveryNoteIntegrationTest.php
class DeliveryNoteIntegrationTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    
    public function testDeliveryNoteCreationFromOrder()
    public function testDeliveryNoteStatusFlow()
    public function testBatchSerialTrackingInDelivery()
}
```

---

### 6. Frontend Implementation

#### 6.1 React Components

**Batch Management:**
```jsx
// lanocrm/src/components/Products/BatchManagement.jsx
const BatchManagement = ({ productId }) => {
  // Component for managing product batches
  // Create, edit, delete batches
  // View batch expiry tracking
};

// lanocrm/src/components/Products/SerialNumberManagement.jsx
const SerialNumberManagement = ({ productId }) => {
  // Component for managing serial numbers
  // Generate, track, sell serial numbers
  // View warranty information
};
```

**Delivery Notes:**
```jsx
// lanocrm/src/components/Delivery/DeliveryNoteForm.jsx
const DeliveryNoteForm = ({ orderId }) => {
  // Component for creating delivery notes
  // Select items, batches, serial numbers
  // Track delivery status
};

// lanocrm/src/components/Delivery/DeliveryNoteList.jsx
const DeliveryNoteList = () => {
  // Component for listing delivery notes
  // Filter by status, date, customer
  // View delivery details
};
```

#### 6.2 Redux Slices

**Batch Management:**
```javascript
// lanocrm/src/store/slices/productBatchSlice.js
const productBatchSlice = createSlice({
  name: 'productBatches',
  initialState: {
    batches: [],
    loading: false,
    error: null
  },
  reducers: {
    // Actions for batch management
  }
});
```

**Delivery Notes:**
```javascript
// lanocrm/src/store/slices/deliveryNoteSlice.js
const deliveryNoteSlice = createSlice({
  name: 'deliveryNotes',
  initialState: {
    deliveryNotes: [],
    loading: false,
    error: null
  },
  reducers: {
    // Actions for delivery note management
  }
});
```

---

### 7. Implementation Timeline

#### Phase 1 (Month 1-3): Critical Features
- Week 1-2: Database migrations for all Priority 1 features
- Week 3-4: Repository layer implementation
- Week 5-6: Service layer implementation
- Week 7-8: Controller layer implementation
- Week 9-10: Frontend components development
- Week 11-12: Testing and bug fixes

#### Phase 2 (Month 4-6): Important Features
- Month 4: Advanced pricing system
- Month 5: Reorder level planning
- Month 6: Quality management and order templates

#### Phase 3 (Month 7-12): Nice-to-have Features
- Month 7-8: Manufacturing integration
- Month 9-10: E-commerce integration
- Month 11-12: Subscription management

---

### 8. Success Metrics

**Technical Metrics:**
- Code coverage > 80%
- API response time < 500ms
- Database query optimization
- Zero critical bugs in production

**Business Metrics:**
- 30% reduction in stock management time
- 25% improvement in delivery accuracy
- 20% reduction in order processing time
- 15% improvement in inventory accuracy

---

### 9. Risk Mitigation

**Technical Risks:**
- Database performance: Implement proper indexing
- Data migration: Create migration scripts
- API compatibility: Version API endpoints
- Testing: Comprehensive test coverage

**Business Risks:**
- User adoption: Provide training and documentation
- Data accuracy: Implement validation and reconciliation
- Process changes: Gradual rollout with fallback options

---

### 10. Conclusion

Kế hoạch này sẽ nâng cao đáng kể tính năng của LanoCRM, giúp nó cạnh tranh với ERPNext trong khi vẫn giữ được kiến trúc sạch và hiệu quả. Việc implement theo từng priority sẽ đảm bảo giá trị mang lại cho người dùng một cách nhanh chóng và giảm thiểu rủi ro.
