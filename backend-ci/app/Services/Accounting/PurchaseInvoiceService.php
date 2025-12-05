<?php

namespace App\Services\Accounting;

use App\Repositories\Accounting\PurchaseInvoiceRepository;
use App\Repositories\Taxes\TaxTemplateRepository;
use App\Validators\PurchaseInvoiceValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * @agent-service: Purchase invoices
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class PurchaseInvoiceService
{
    protected PurchaseInvoiceRepository $repo;
    protected PurchaseInvoiceValidator $validator;
    protected TaxTemplateRepository $taxTemplates;
    protected AccountingService $accounting;

    public function __construct(
        ?PurchaseInvoiceRepository $repo = null,
        ?PurchaseInvoiceValidator $validator = null,
        ?TaxTemplateRepository $taxTemplates = null,
        ?AccountingService $accounting = null
    ) {
        $this->repo = $repo ?? new PurchaseInvoiceRepository();
        $this->validator = $validator ?? new PurchaseInvoiceValidator();
        $this->taxTemplates = $taxTemplates ?? new TaxTemplateRepository();
        $this->accounting = $accounting ?? new AccountingService();
    }

    /**
     * Preview totals.
     *
     * @agent-use: POST /api/purchase-invoices/preview
     */
    public function preview(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        return ['success' => true, 'data' => $this->calculateTotals($data)];
    }

    /**
     * Create draft purchase invoice.
     *
     * @agent-use: POST /api/purchase-invoices
     */
    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $totals = $this->calculateTotals($data);
        $number = $this->repo->nextNumber();
        $invoiceRow = [
            'invoice_number' => $number,
            'supplier_id' => $data['supplier_id'],
            'posting_date' => $data['posting_date'],
            'due_date' => $data['due_date'],
            'status' => 'draft',
            'currency' => $data['currency'],
            'exchange_rate' => $data['exchange_rate'],
            'total' => $totals['total'],
            'taxes_total' => $totals['taxes_total'],
            'grand_total' => $totals['grand_total'],
            'rounding_adjustment' => $data['rounding_adjustment'],
            'debit_account_id' => $data['debit_account_id'],
            'credit_account_id' => $data['credit_account_id'],
        ];
        $invoice = $this->repo->create($invoiceRow, $totals['items'], $totals['taxes']);
        return ['success' => true, 'data' => $invoice];
    }

    /**
     * Submit and post GL (reverse payable).
     *
     * @agent-use: POST /api/purchase-invoices/{id}/submit
     */
    public function submit(int $id): array
    {
        $invoice = $this->repo->findById($id);
        if (! $invoice) {
            throw new RuntimeException('Invoice not found');
        }
        if ($invoice['status'] !== 'draft') {
            throw new InvalidArgumentException('Only draft invoices can be submitted');
        }

        $this->postGL($invoice, false);
        $this->repo->updateStatus($id, 'submitted');
        return ['success' => true, 'data' => $this->repo->findById($id)];
    }

    /**
     * Cancel and reverse GL.
     *
     * @agent-use: POST /api/purchase-invoices/{id}/cancel
     */
    public function cancel(int $id): array
    {
        $invoice = $this->repo->findById($id);
        if (! $invoice) {
            throw new RuntimeException('Invoice not found');
        }
        if ($invoice['status'] !== 'submitted') {
            throw new InvalidArgumentException('Only submitted invoices can be cancelled');
        }
        $this->postGL($invoice, true);
        $this->repo->updateStatus($id, 'cancelled');
        return ['success' => true, 'data' => $this->repo->findById($id)];
    }

    /**
     * Get invoice.
     *
     * @agent-use: GET /api/purchase-invoices/{id}
     */
    public function get(int $id): array
    {
        $invoice = $this->repo->findById($id);
        if (! $invoice) {
            throw new RuntimeException('Invoice not found');
        }
        return ['success' => true, 'data' => $invoice];
    }

    private function calculateTotals(array $data): array
    {
        $items = $data['items'];
        $subtotal = array_sum(array_column($items, 'amount'));

        $taxes = $data['taxes'];
        $taxAmount = 0.0;
        foreach ($taxes as $idx => $tax) {
            $amount = round($subtotal * ($tax['rate_percent'] / 100), 2);
            $taxes[$idx]['amount'] = $amount;
            $taxAmount += $amount;
        }

        $grand = round($subtotal + $taxAmount + $data['rounding_adjustment'], 2);

        return [
            'items' => $items,
            'taxes' => $taxes,
            'total' => round($subtotal, 2),
            'taxes_total' => round($taxAmount, 2),
            'grand_total' => $grand,
        ];
    }

    private function postGL(array $invoice, bool $reverse): void
    {
        $amount = $invoice['grand_total'];
        $entries = $reverse
            ? [
                [
                    'account_id' => $invoice['credit_account_id'],
                    'debit' => $amount,
                    'credit' => 0,
                    'reference_type' => 'purchase_invoice',
                    'reference_id' => $invoice['id'],
                    'remarks' => 'Reverse purchase invoice ' . $invoice['invoice_number'],
                ],
                [
                    'account_id' => $invoice['debit_account_id'],
                    'debit' => 0,
                    'credit' => $amount,
                    'reference_type' => 'purchase_invoice',
                    'reference_id' => $invoice['id'],
                    'remarks' => 'Reverse purchase invoice ' . $invoice['invoice_number'],
                ],
            ]
            : [
                [
                    'account_id' => $invoice['debit_account_id'],
                    'debit' => $amount,
                    'credit' => 0,
                    'reference_type' => 'purchase_invoice',
                    'reference_id' => $invoice['id'],
                    'remarks' => 'Purchase invoice ' . $invoice['invoice_number'],
                ],
                [
                    'account_id' => $invoice['credit_account_id'],
                    'debit' => 0,
                    'credit' => $amount,
                    'reference_type' => 'purchase_invoice',
                    'reference_id' => $invoice['id'],
                    'remarks' => 'Purchase invoice ' . $invoice['invoice_number'],
                ],
            ];

        $this->accounting->postJournal([
            'posting_date' => $invoice['posting_date'],
            'entries' => $entries,
        ]);
    }
}
