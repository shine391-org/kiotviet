---
title: "TASK 01: Payment Methods Implementation"
id: "PAY-001"
priority: "P0 (Blocker)"
estimated_effort: "2 days"
status: "Done"
module: "Order Workflow"
type: "Implementation Task"
tags: ["task", "payment-methods", "CRUD", "backend", "database", "API"]
purpose: "Implement the payment_methods master data table and its associated CRUD operations, including seeding, validation, and API endpoints."
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

# TASK_01: Payment Methods Implementation

**Priority:** P0 (Blocker)

**Estimated Effort:** 2 days

**Dependencies:** None

**Status:** Done

---

## 🎯 OBJECTIVE

Implement **payment_methods** master data table and CRUD operations.

---

## 📋 REQUIREMENTS

### **Functional Requirements**

- [x]  Create payment_methods table
- [x]  Seed 5 default payment methods
- [x]  Create API endpoints for CRUD
- [x]  Implement validation rules
- [x]  Add payment method activation/deactivation

### **Non-Functional Requirements**

- [x]  Response time < 100ms for list
- [x]  Support multi-language names
- [x]  Cache payment methods

---

## 🗄️ DATABASE SCHEMA

### **Migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('UPPERCASE code: CASH, BANK_TRANSFER, etc.');
            $table->string('name')->comment('Display name');
            $table->text('description')->nullable()->comment('Description for customers');
            $table->boolean('is_active')->default(true)->comment('Is method available?');
            $table->integer('display_order')->default(0)->comment('Display order in UI');
            $table->timestamps();
            
            // Indexes
            $table->index('is_active');
            $table->index('display_order');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_methods');
    }
};
```

---

### **Seeder**

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    public function run()
    {
        $methods = [
            [
                'code' => 'CASH',
                'name' => 'Tiền mặt',
                'description' => 'Thanh toán bằng tiền mặt tại cửa hàng',
                'is_active' => true,
                'display_order' => 1
            ],
            [
                'code' => 'BANK_TRANSFER',
                'name' => 'Chuyển khoản ngân hàng',
                'description' => 'Chuyển khoản qua tài khoản ngân hàng',
                'is_active' => true,
                'display_order' => 2
            ],
            [
                'code' => 'CARD',
                'name' => 'Thẻ tín dụng/ghi nợ',
                'description' => 'Thanh toán bằng thẻ Visa/Mastercard/JCB',
                'is_active' => true,
                'display_order' => 3
            ],
            [
                'code' => 'COD',
                'name' => 'Thu hộ (COD)',
                'description' => 'Thanh toán khi nhận hàng. Phí COD: 15,000đ',
                'is_active' => true,
                'display_order' => 4
            ],
            [
                'code' => 'E_WALLET',
                'name' => 'Ví điện tử',
                'description' => 'Thanh toán qua MoMo, ZaloPay, VNPay',
                'is_active' => true,
                'display_order' => 5
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::create($method);
        }
    }
}
```

---

## 🏗️ MODEL

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    // Relationships
    public function orders()
    {
        return $this->hasMany(Order::class, 'payment_method', 'code');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    // Accessors
    public function getIsUsedAttribute()
    {
        return $this->orders()->exists();
    }
}
```

---

## 🌐 API ENDPOINTS

### **Routes**

```php
// routes/api.php
Route::prefix('payment-methods')->group(function () {
    Route::get('/', [PaymentMethodController::class, 'index']);
    Route::get('/{id}', [PaymentMethodController::class, 'show']);
    Route::post('/', [PaymentMethodController::class, 'store']);
    Route::put('/{id}', [PaymentMethodController::class, 'update']);
    Route::delete('/{id}', [PaymentMethodController::class, 'destroy']);
    Route::patch('/{id}/activate', [PaymentMethodController::class, 'activate']);
    Route::patch('/{id}/deactivate', [PaymentMethodController::class, 'deactivate']);
});
```

---

### **Controller**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $methods = Cache::remember('payment_methods', 3600, function () {
            return PaymentMethod::active()
                ->ordered()
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => $methods
        ]);
    }

    public function show($id)
    {
        $method = PaymentMethod::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $method
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|regex:/^[A-Z_]+$/|unique:payment_methods,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'display_order' => 'integer|min:0',
        ]);

        $method = PaymentMethod::create($validated);

        Cache::forget('payment_methods');

        return response()->json([
            'success' => true,
            'message' => 'Payment method created successfully',
            'data' => $method
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $method = PaymentMethod::findOrFail($id);

        $validated = $request->validate([
            'code' => 'string|max:50|regex:/^[A-Z_]+$/|unique:payment_methods,code,' . $id,
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'display_order' => 'integer|min:0',
        ]);

        $method->update($validated);

        Cache::forget('payment_methods');

        return response()->json([
            'success' => true,
            'message' => 'Payment method updated successfully',
            'data' => $method
        ]);
    }

    public function destroy($id)
    {
        $method = PaymentMethod::findOrFail($id);

        // Check if used in orders
        if ($method->is_used) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete payment method that is used in orders',
                'error_code' => 'PAY_METHOD_IN_USE'
            ], 400);
        }

        $method->delete();

        Cache::forget('payment_methods');

        return response()->json([
            'success' => true,
            'message' => 'Payment method deleted successfully'
        ]);
    }

    public function activate($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->update(['is_active' => true]);

        Cache::forget('payment_methods');

        return response()->json([
            'success' => true,
            'message' => 'Payment method activated',
            'data' => $method
        ]);
    }

    public function deactivate($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->update(['is_active' => false]);

        Cache::forget('payment_methods');

        return response()->json([
            'success' => true,
            'message' => 'Payment method deactivated',
            'data' => $method
        ]);
    }
}
```

---

## ✅ VALIDATION RULES

See [**PAYMENT_](https://www.notion.so/PAYMENT_RULES-Payment-Validation-Rules-d0f287e7743c4a30b777f120adf93cb5?pvs=21)[RULES.md](http://RULES.md)**

---

## 🧪 TESTING

### **Unit Tests**

```php
<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_payment_method()
    {
        $method = PaymentMethod::create([
            'code' => 'TEST_METHOD',
            'name' => 'Test Method',
            'is_active' => true,
            'display_order' => 10
        ]);

        $this->assertDatabaseHas('payment_methods', [
            'code' => 'TEST_METHOD',
            'name' => 'Test Method'
        ]);
    }

    /** @test */
    public function code_must_be_uppercase()
    {
        $this->expectException(\Exception::class);

        PaymentMethod::create([
            'code' => 'lowercase',
            'name' => 'Test'
        ]);
    }

    /** @test */
    public function it_has_orders_relationship()
    {
        $method = PaymentMethod::factory()->create(['code' => 'CASH']);

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $method->orders()
        );
    }
}
```

---

### **Feature Tests**

```php
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentMethodApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_list_payment_methods()
    {
        PaymentMethod::factory()->count(3)->create(['is_active' => true]);

        $response = $this->getJson('/api/payment-methods');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'code', 'name', 'is_active']
                ]
            ]);
    }

    /** @test */
    public function it_can_create_payment_method()
    {
        $data = [
            'code' => 'NEW_METHOD',
            'name' => 'New Method',
            'description' => 'Test description',
            'is_active' => true,
            'display_order' => 10
        ];

        $response = $this->postJson('/api/payment-methods', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'code' => 'NEW_METHOD'
                ]
            ]);
    }

    /** @test */
    public function it_cannot_delete_used_payment_method()
    {
        $method = PaymentMethod::factory()->create();
        // Create order using this method
        Order::factory()->create(['payment_method' => $method->code]);

        $response = $this->deleteJson("/api/payment-methods/{$method->id}");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot delete payment method that is used in orders',
                'error_code' => 'PAY_METHOD_IN_USE'
            ]);
    }
}
```

---

## 📝 ACCEPTANCE CRITERIA

- [x]  Payment methods table created with all fields
- [x]  5 default methods seeded
- [x]  API returns only active methods by default
- [x]  Methods ordered by display_order
- [x]  Cannot delete method used in orders
- [x]  Code must be UPPERCASE
- [x]  Cache invalidated on changes
- [x]  All tests passing

---

## 🔗 RELATED DOCUMENTS

- [**PAYMENT_METHODS_](https://www.notion.so/PAYMENT_METHODS_TABLE-Payment-Methods-Schema-ac2e0402fa7b48099e71738e419ccff5?pvs=21)[TABLE.md](http://TABLE.md)** - Schema details
- [**PAYMENT_](https://www.notion.so/PAYMENT_RULES-Payment-Validation-Rules-d0f287e7743c4a30b777f120adf93cb5?pvs=21)[RULES.md](http://RULES.md)** - Validation rules
- [**SAMPLE_](https://www.notion.so/SAMPLE_DATA-Sample-Data-Examples-5baed5e554654be785e562c63b5d42e5?pvs=21)[DATA.md](http://DATA.md)** - Sample data
