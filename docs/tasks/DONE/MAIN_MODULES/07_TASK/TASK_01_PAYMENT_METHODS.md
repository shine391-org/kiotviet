---
title: "TASK 01: Payment Methods Implementation (CodeIgniter 4)"
id: "PAY-001-CI4"
priority: "P0 (Blocker)"
estimated_effort: "1 day"
status: "Done"
module: "Order Workflow"
type: "Implementation Task"
tags: ["task", "payment-methods", "CRUD", "backend", "database", "API", "codeigniter"]
purpose: "Implement the payment_methods master data table and its associated CRUD operations, including seeding, validation, and API endpoints using CodeIgniter 4."
location: "docs/tasks/MAIN_MODULES/07_TASK"
related_to:
  - id: "PAYMENT-METHODS-TABLE-01"
    description: "Details the schema to be implemented."
  - id: "PAYMENT-RULES-01"
    description: "Defines the validation rules to be implemented."
  - id: "SAMPLE-DATA-EXAMPLES-01"
    description: "Provides sample data relevant to payment methods."
  - id: "ORDER-WORKFLOW-INDEX"
    description: "Task listed in the module index."
---

# TASK_01: Payment Methods Implementation (CodeIgniter 4)

**Priority:** P0 (Blocker)

**Estimated Effort:** 1 day

**Dependencies:** None

**Status:** Done

---

## 🎯 OBJECTIVE

Implement **payment_methods** master data table and CRUD operations using **CodeIgniter 4**, following the project's Clean Architecture patterns.

---

## 📋 REQUIREMENTS

### **Functional Requirements**

- [x]  Create `payment_methods` table using CodeIgniter 4 Migrations.
- [x]  Seed 5 default payment methods.
- [x]  Create API endpoints for CRUD operations.
- [x]  Implement validation rules in a dedicated Validator class.
- [x]  Implement activation/deactivation logic.

### **Non-Functional Requirements**

- [x]  API response time for list operations should be under 100ms.
- [x]  Support multi-language names via a JSON field (`name_translations`).
- [x]  Utilize CodeIgniter's caching for the active payment methods list.

---

## 🗄️ DATABASE SCHEMA (CodeIgniter 4)

### **Migration: `2025-11-24-000007_CreatePaymentMethodTables.php`**

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentMethodTables extends Migration
{
    public function up()
    {
        $jsonType = strtolower($this->db->DBDriver) === 'sqlite3' ? 'TEXT' : 'JSON';

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'comment' => 'UPPERCASE code'],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'name_translations' => ['type' => $jsonType, 'null' => true, 'comment' => 'Optional i18n names'],
            'description' => ['type' => 'TEXT', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'display_order' => ['type' => 'INT', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('is_active');
        $this->forge->addKey('display_order');
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('payment_methods', true);
    }

    public function down()
    {
        $this->forge->dropTable('payment_methods', true);
    }
}
```

---

### **Seeder: `PaymentMethodSeeder.php`**

```php
<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class PaymentMethodSeeder extends Seeder
{
    public function run()
    {
        $now = Time::now();
        $methods = [
            [
                'code' => 'CASH',
                'name' => 'Tiền mặt',
                'is_active' => 1,
                'display_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            // ... other methods
        ];

        $this->db->table('payment_methods')->ignore(true)->insertBatch($methods);
    }
}
```

---

## 🏗️ ARCHITECTURE (CodeIgniter 4)

### **Model: `PaymentMethodModel.php`**

```php
<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentMethodModel extends Model
{
    protected $table = 'payment_methods';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'code', 'name', 'name_translations', 'description', 
        'is_active', 'display_order', 'created_at', 
        'updated_at', 'deleted_at',
    ];
}
```

### **Repository: `PaymentMethodRepository.php`**

Handles all database queries using CodeIgniter's Query Builder.

```php
// Example: Find all active methods
public function activeOrdered(): array
{
    $rows = $this->model->builder()
        ->where('deleted_at', null)
        ->where('is_active', 1)
        ->orderBy('display_order', 'ASC')
        ->get()->getResultArray();
    
    return array_map(fn ($row) => $this->hydrate($row), $rows);
}
```

### **Service: `PaymentMethodService.php`**

Contains business logic, caching, and orchestration.

```php
// Example: Listing methods with cache logic
public function list(array $filters): array
{
    if ($this->shouldUseCache($validated)) {
        $cached = $this->cache->get(self::CACHE_ACTIVE_KEY);
        if (is_array($cached)) {
            // return from cache
        }
    }
    // ... fetch from repo and save to cache
}
```

---

## 🌐 API ENDPOINTS (CodeIgniter 4)

### **Routes: `app/Config/Routes.php`**

```php
$routes->group('api', ['filter' => 'jwtAuth'], function ($routes) {
    $routes->resource('payment-methods', [
        'controller' => 'PaymentMethodsController',
        'only' => ['index', 'show', 'create', 'update', 'delete']
    ]);
    $routes->patch('payment-methods/(:num)/activate', 'PaymentMethodsController::activate/$1');
    $routes->patch('payment-methods/(:num)/deactivate', 'PaymentMethodsController::deactivate/$1');
});
```

### **Controller: `PaymentMethodsController.php`**

A thin controller that delegates all work to the `PaymentMethodService`.

```php
<?php
namespace App\Controllers\Api;

use App\Services\PaymentMethods\PaymentMethodService;
use CodeIgniter\API\ResponseTrait;

class PaymentMethodsController extends BaseController
{
    use ResponseTrait;
    protected PaymentMethodService $service;

    public function __construct()
    {
        $this->service = service('paymentMethodService');
    }

    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }
    // ... other methods delegate to $this->service
}
```

---

## 🧪 TESTING (CodeIgniter 4)

### **Unit Test: `tests/Services/PaymentMethodServiceTest.php`**

Focuses on the business logic within the service layer.

```php
<?php
namespace Tests\Services;

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

class PaymentMethodServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    // ... tests for create, update, delete, business rules
}
```

### **Integration Test: `tests/Integration/Payments/PaymentMethodsApiTest.php`**

Performs full-stack tests on the API endpoints.

```php
<?php
namespace Tests\Integration\Payments;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class PaymentMethodsApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    
    public function test_list_returns_active_methods_only_and_sorted(): void
    {
        $res = $this->withHeaders($this->authHeaders())->get('api/payment-methods');
        $res->assertStatus(200);
        // ... more assertions
    }
}
```

---

## 📝 ACCEPTANCE CRITERIA

- [x]  `payment_methods` table created via CodeIgniter Migration.
- [x]  5 default methods seeded.
- [x]  API returns only active methods by default.
- [x]  Methods are ordered by `display_order`.
- [x]  Cannot delete a method that is already used in an order.
- `code` must be unique and uppercase.
- [x]  Cache is properly invalidated on `create`, `update`, and `delete`.
- [x]  All unit and integration tests are passing.
