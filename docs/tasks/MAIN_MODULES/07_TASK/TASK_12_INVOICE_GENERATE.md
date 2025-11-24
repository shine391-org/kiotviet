---
title: "TASK 12: Invoice Generation Implementation"
id: "TASK-12-INVOICE-GENERATE-01"
priority: "P2 (Medium)"
estimated_effort: "5 days"
dependencies: "TASK_02, TASK_05"
status: "Blocked"
module: "Order Workflow"
type: "Implementation Task"
tags: ["task", "invoice", "generation", "VAT", "PDF", "API", "backend"]
purpose: "Implement invoice generation logic from completed orders, including unique invoice number generation, VAT calculation, multi-order aggregation, and on-demand PDF export."
location: "docs/tasks/MAIN_MODULES/07_TASK"
related_to:
  - id: "INVOICE-FLOW-01"
    description: "Describes the invoice generation workflow."
  - id: "INVOICES-TABLES-01"
    description: "Schema implemented by this task."
  - id: "INVOICE-RULES-01"
    description: "Validation rules implemented by this task."
  - id: "TASK-02-INVOICES-SCHEMA-01"
    description: "Dependency: Invoices Schema implementation."
  - id: "TASK-05-ORDER-CREATE-01"
    description: "Dependency: Order Create implementation."
  - id: "ORDER-WORKFLOW-INDEX"
    description: "Task listed in the module index."
---

# TASK_12: Invoice Generation Implementation

**Priority:** P2 (Medium)

**Estimated Effort:** 5 days

**Dependencies:** TASK_02, TASK_05

**Status:** Blocked

---

## 🎯 OBJECTIVE

Implement **invoice generation** from orders with VAT calculation and PDF export.

---

## 📋 REQUIREMENTS

### **Invoice Creation**

- ✅ Generate unique invoice number per branch
- ✅ Support multiple orders in one invoice
- ✅ Calculate VAT automatically
- ✅ Customer must have tax_code (B2B only)
- ✅ Orders must be from same customer and branch

### **PDF Generation**

- ✅ Generate on-demand
- ✅ Cache generated PDFs
- ✅ Include company branding
- ✅ Support Vietnamese language

See [**INVOICE_](https://www.notion.so/INVOICE_RULES-Invoice-Validation-Rules-183d4e84332f42398496f1ce0f012935?pvs=21)[RULES.md](http://RULES.md)**

---

## 🏗️ SERVICE IMPLEMENTATION

### **InvoiceGenerationService**

```php
<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use App\Exceptions\ValidationException;

class InvoiceGenerationService
{
    public function __construct(
        private InvoiceNumberGenerator $numberGenerator,
        private VATCalculator $vatCalculator
    ) {}

    public function create(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            // Validate
            $this->validate($data);
            
            // Get orders
            $orders = Order::whereIn('id', $data['order_ids'])
                ->with('customer')
                ->lockForUpdate()
                ->get();
            
            // Validate orders
            $this->validateOrders($orders, $data);
            
            // Generate invoice number
            $invoiceNumber = $this->numberGenerator->generate($data['branch_id']);
            
            // Calculate totals
            $subtotal = $orders->sum('total');
            
            $vat = $this->vatCalculator->calculate(
                $subtotal,
                $data['vat_rate']
            );
            
            // Create invoice
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $data['customer_id'],
                'branch_id' => $data['branch_id'],
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'subtotal' => $subtotal,
                'vat_rate' => $data['vat_rate'],
                'vat_amount' => $vat['vat_amount'],
                'total' => $vat['total'],
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);
            
            // Link orders
            $invoice->orders()->attach($data['order_ids']);
            
            return $invoice->load(['orders', 'customer', 'branch']);
        });
    }

    private function validate(array $data): void
    {
        // Customer must have tax_code
        $customer = Customer::find($data['customer_id']);
        if (!$customer->tax_code) {
            throw new ValidationException(
                'Customer must have tax code for invoice generation',
                'INV_NO_TAX_CODE'
            );
        }
        
        // Must have at least one order
        if (empty($data['order_ids'])) {
            throw new ValidationException(
                'Invoice must include at least one order',
                'INV_NO_ORDERS'
            );
        }
    }

    private function validateOrders($orders, array $data): void
    {
        // All orders must belong to same customer
        $customerIds = $orders->pluck('customer_id')->unique();
        if ($customerIds->count() > 1) {
            throw new ValidationException(
                'All orders must belong to the same customer',
                'INV_MIXED_CUSTOMERS'
            );
        }
        
        if ($customerIds->first() !== $data['customer_id']) {
            throw new ValidationException(
                'Orders do not belong to specified customer',
                'INV_CUSTOMER_MISMATCH'
            );
        }
        
        // All orders must be completed
        $incompleteOrders = $orders->where('status', '!=', 'completed');
        if ($incompleteOrders->isNotEmpty()) {
            throw new ValidationException(
                'All orders must be completed',
                'INV_INCOMPLETE_ORDERS'
            );
        }
        
        // Orders cannot already have invoice
        foreach ($orders as $order) {
            if ($order->invoices()->exists()) {
                throw new ValidationException(
                    "Order #{$order->order_number} already has an invoice",
                    'INV_ORDER_ALREADY_INVOICED'
                );
            }
        }
    }
}
```

---

## 📄 PDF GENERATION SERVICE

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
        // Check if PDF already exists
        if ($invoice->pdf_path && Storage::exists($invoice->pdf_path)) {
            return Storage::url($invoice->pdf_path);
        }
        
        // Load relationships
        $invoice->load([
            'customer',
            'branch',
            'orders.items',
            'createdBy'
        ]);
        
        // Generate PDF
        $pdf = PDF::loadView('invoices.template', [
            'invoice' => $invoice
        ])
        ->setPaper('a4')
        ->setOption('margin-top', 10)
        ->setOption('margin-bottom', 10);
        
        // Save to storage
        $year = $invoice->issue_date->format('Y');
        $month = $invoice->issue_date->format('m');
        $filename = "{$invoice->invoice_number}.pdf";
        $path = "invoices/{$year}/{$month}/{$filename}";
        
        Storage::put($path, $pdf->output());
        
        // Update invoice
        $invoice->update(['pdf_path' => $path]);
        
        return Storage::url($path);
    }
}
```

---

## 🌐 API CONTROLLER

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InvoiceGenerationService;
use App\Services\InvoicePDFGenerator;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceGenerationService $invoiceService,
        private InvoicePDFGenerator $pdfGenerator
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'branch_id' => 'required|integer|exists:branches,id',
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'required|integer|exists:orders,id',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after:issue_date',
            'vat_rate' => 'required|numeric|min:0|max:1',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $invoice = $this->invoiceService->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'data' => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer' => [
                        'id' => $invoice->customer->id,
                        'name' => $invoice->customer->name,
                        'tax_code' => $invoice->customer->tax_code,
                    ],
                    'orders' => $invoice->orders->map(fn($order) => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'total' => $order->total,
                    ]),
                    'issue_date' => $invoice->issue_date->toDateString(),
                    'due_date' => $invoice->due_date?->toDateString(),
                    'subtotal' => $invoice->subtotal,
                    'vat_rate' => $invoice->vat_rate,
                    'vat_amount' => $invoice->vat_amount,
                    'total' => $invoice->total,
                ]
            ], 201);
        } catch (\App\Exceptions\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ], 400);
        }
    }

    public function downloadPDF(int $id)
    {
        $invoice = Invoice::findOrFail($id);
        
        try {
            $pdfUrl = $this->pdfGenerator->generate($invoice);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'pdf_url' => $pdfUrl,
                    'invoice_number' => $invoice->invoice_number,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

---

## 🧪 TESTING

```php
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceGenerationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_invoice_from_orders()
    {
        $user = User::factory()->admin()->create();
        $customer = Customer::factory()->create(['tax_code' => '0123456789']);
        
        $order1 = Order::factory()->create([
            'customer_id' => $customer->id,
            'branch_id' => 1,
            'status' => 'completed',
            'total' => 1000000
        ]);
        
        $order2 = Order::factory()->create([
            'customer_id' => $customer->id,
            'branch_id' => 1,
            'status' => 'completed',
            'total' => 2000000
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/invoices', [
                'customer_id' => $customer->id,
                'branch_id' => 1,
                'order_ids' => [$order1->id, $order2->id],
                'issue_date' => '2024-11-24',
                'due_date' => '2024-12-24',
                'vat_rate' => 0.10,
                'notes' => 'Invoice for November 2024'
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'invoice_number',
                    'subtotal',
                    'vat_amount',
                    'total'
                ]
            ]);

        $this->assertDatabaseHas('invoices', [
            'customer_id' => $customer->id,
            'subtotal' => 3000000,
            'vat_rate' => 0.10,
            'vat_amount' => 300000,
            'total' => 3300000
        ]);
    }

    /** @test */
    public function it_cannot_create_invoice_without_tax_code()
    {
        $user = User::factory()->admin()->create();
        $customer = Customer::factory()->create(['tax_code' => null]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'completed'
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/invoices', [
                'customer_id' => $customer->id,
                'branch_id' => 1,
                'order_ids' => [$order->id],
                'issue_date' => '2024-11-24',
                'vat_rate' => 0.10
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'error_code' => 'INV_NO_TAX_CODE'
            ]);
    }
}
```

---

## 📝 ACCEPTANCE CRITERIA

- [x]  Invoice number generated uniquely per branch
- [x]  Multiple orders supported
- [x]  VAT calculated correctly
- [x]  Customer must have tax_code
- [x]  Orders must be completed
- [x]  Cannot invoice same order twice
- [x]  PDF generation works
- [x]  Tests passing

---

## 🔗 RELATED DOCUMENTS

- [**INVOICE_](https://www.notion.so/INVOICE_FLOW-Invoice-Generation-Workflow-f870e3cf753e4c1abc41b11c6fe4748d?pvs=21)[FLOW.md](http://FLOW.md)**
- [**INVOICES_](https://www.notion.so/INVOICES_TABLES-Invoices-Schema-9ff5db4f3f3a4ea78d2298ecca309a73?pvs=21)[TABLES.md](http://TABLES.md)**
- [**INVOICE_](https://www.notion.so/INVOICE_RULES-Invoice-Validation-Rules-183d4e84332f42398496f1ce0f012935?pvs=21)[RULES.md](http://RULES.md)**