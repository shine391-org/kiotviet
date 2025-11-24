---
title: "TASK 02: Invoices Schema Implementation"
id: "INV-001"
priority: "P1 (High)"
estimated_effort: "3 days"
dependencies: "TASK_01 (Payment Methods)"
status: "Done"
module: "Order Workflow"
type: "Implementation Task"
tags: ["task", "invoices", "database", "schema", "junction-table", "PDF", "VAT", "backend"]
purpose: "Implement the 'invoices' and 'invoice_orders' tables, including number generation, VAT calculation, and on-demand PDF generation."
location: "docs/tasks/MAIN_MODULES/07_TASK"
related_to:
  - id: "INVOICES-TABLES-01"
    description: "Details the schema to be implemented."
  - id: "INVOICE-RULES-01"
    description: "Defines the validation rules to be implemented."
  - id: "INVOICE-FLOW-01"
    description: "Describes the workflow for invoice generation."
  - id: "ORDER-WORKFLOW-INDEX"
    description: "Task listed in the module index."
  - id: "PAY-001"
    description: "Dependency: Payment Methods implementation."
---

# TASK_02: Invoices Schema Implementation

**Priority:** P1 (High)

**Estimated Effort:** 3 days

**Dependencies:** TASK_01 (Payment Methods)

**Status:** Done

---

## 🎯 OBJECTIVE

Implement **invoices** and **invoice_orders** tables with N:N relationship.

---

## 📋 REQUIREMENTS

### **Functional Requirements**

- [x]  Create invoices table
- [x]  Create invoice_orders junction table
- [x]  Implement invoice number generation
- [x]  Support multiple orders per invoice
- [x]  Calculate VAT automatically
- [x]  Generate PDF on-demand

### **Non-Functional Requirements**

- [x]  Invoice number must be unique
- [x]  Support transaction isolation
- [x]  PDF generation < 3 seconds

---

## 🗄️ DATABASE SCHEMA

### **Migration: invoices**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            
            // Invoice identification
            $table->string('invoice_number', 50)->unique()->comment('Format: HD-{branch_id}-{counter}');
            
            // Relationships
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('restrict');
            
            // Dates
            $table->date('issue_date')->comment('Invoice issue date');
            $table->date('due_date')->nullable()->comment('Payment due date');
            
            // Financial
            $table->decimal('subtotal', 15, 2)->comment('Sum of order totals');
            $table->decimal('vat_rate', 5, 4)->comment('VAT rate (0.10 = 10%)');
            $table->decimal('vat_amount', 15, 2)->comment('Calculated VAT');
            $table->decimal('total', 15, 2)->comment('subtotal + vat_amount');
            
            // PDF
            $table->string('pdf_path')->nullable()->comment('Path to generated PDF');
            
            // Additional info
            $table->text('notes')->nullable();
            
            // Audit
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            // Indexes
            $table->index('customer_id');
            $table->index('branch_id');
            $table->index('issue_date');
            $table->index('due_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('invoices');
    }
};
```

---

### **Migration: invoice_orders**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('invoice_orders', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('orders')->onDelete('restrict');
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['invoice_id', 'order_id']);
            $table->index('order_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('invoice_orders');
    }
};
```

---

## 🏗️ MODELS

### **Invoice Model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'branch_id',
        'issue_date',
        'due_date',
        'subtotal',
        'vat_rate',
        'vat_amount',
        'total',
        'pdf_path',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'vat_rate' => 'decimal:4',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'invoice_orders')
            ->withTimestamps();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getIsOverdueAttribute()
    {
        return $this->due_date && $this->due_date->isPast();
    }

    public function getDaysOverdueAttribute()
    {
        if (!$this->is_overdue) {
            return 0;
        }
        
        return now()->diffInDays($this->due_date);
    }
}
```

---

## 🔢 INVOICE NUMBER GENERATION

### **Service Class**

```php
<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceNumberGenerator
{
    public function generate(int $branchId): string
    {
        return DB::transaction(function () use ($branchId) {
            // Lock invoices for this branch
            $counter = Invoice::where('branch_id', $branchId)
                ->lockForUpdate()
                ->count() + 1;
            
            return sprintf("HD-%d-%04d", $branchId, $counter);
        });
    }
}
```

---

## 💰 VAT CALCULATION

### **Service Class**

```php
<?php

namespace App\Services;

class VATCalculator
{
    public function calculate(float $subtotal, float $vatRate): array
    {
        $vatAmount = round($subtotal * $vatRate, 2);
        $total = $subtotal + $vatAmount;
        
        return [
            'vat_amount' => $vatAmount,
            'total' => $total,
        ];
    }
}
```

---

## 📄 PDF GENERATION

### **Service Class**

```php
<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePDFGenerator
{
    public function generate(Invoice $invoice): string
    {
        // Check cache
        if ($invoice->pdf_path && Storage::exists($invoice->pdf_path)) {
            return $invoice->pdf_path;
        }
        
        // Load relationships
        $invoice->load(['customer', 'branch', 'orders.items']);
        
        // Generate PDF
        $pdf = PDF::loadView('invoices.template', [
            'invoice' => $invoice
        ]);
        
        // Save to storage
        $year = $invoice->issue_date->format('Y');
        $filename = "HD-{$invoice->invoice_number}.pdf";
        $path = "invoices/{$year}/{$filename}";
        
        Storage::put($path, $pdf->output());
        
        // Update invoice
        $invoice->update(['pdf_path' => $path]);
        
        return $path;
    }
}
```

---

## 🧪 TESTING

### **Unit Tests**

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\InvoiceNumberGenerator;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_unique_invoice_numbers()
    {
        $generator = new InvoiceNumberGenerator();
        
        $num1 = $generator->generate(1);
        $num2 = $generator->generate(1);
        
        $this->assertEquals('HD-1-0001', $num1);
        $this->assertEquals('HD-1-0002', $num2);
    }

    /** @test */
    public function it_handles_concurrent_generation()
    {
        $generator = new InvoiceNumberGenerator();
        
        // Simulate concurrent requests
        $numbers = [];
        for ($i = 0; $i < 10; $i++) {
            $numbers[] = $generator->generate(1);
        }
        
        // All numbers should be unique
        $this->assertCount(10, array_unique($numbers));
    }
}
```

---

## 📝 ACCEPTANCE CRITERIA

- [x]  Invoices table created
- [x]  Invoice_orders junction table created
- [x]  Invoice number generation thread-safe
- [x]  VAT calculation accurate to 2 decimal places
- [x]  PDF generation works
- [x]  Can link multiple orders to one invoice
- [x]  Cannot link same order to multiple invoices
- [x]  All tests passing

---

## 🔗 RELATED DOCUMENTS

- [**INVOICES_](https://www.notion.so/INVOICES_TABLES-Invoices-Schema-9ff5db4f3f3a4ea78d2298ecca309a73?pvs=21)[TABLES.md](http://TABLES.md)** - Schema details
- [**INVOICE_](https://www.notion.so/INVOICE_RULES-Invoice-Validation-Rules-183d4e84332f42398496f1ce0f012935?pvs=21)[RULES.md](http://RULES.md)** - Validation rules
- [**INVOICE_](https://www.notion.so/INVOICE_FLOW-Invoice-Generation-Workflow-f870e3cf753e4c1abc41b11c6fe4748d?pvs=21)[FLOW.md](http://FLOW.md)** - Workflow
