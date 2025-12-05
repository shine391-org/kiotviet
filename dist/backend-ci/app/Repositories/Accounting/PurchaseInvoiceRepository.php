<?php

namespace App\Repositories\Accounting;

use App\Models\PurchaseInvoiceModel;
use App\Models\PurchaseInvoiceItemModel;
use App\Models\PurchaseInvoiceTaxModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Purchase invoices
 * @agent-pattern: Repository with children
 * @agent-reusable: MEDIUM
 */
class PurchaseInvoiceRepository
{
    protected PurchaseInvoiceModel $invoices;
    protected PurchaseInvoiceItemModel $items;
    protected PurchaseInvoiceTaxModel $taxes;
    protected BaseConnection $db;

    public function __construct(
        ?PurchaseInvoiceModel $invoices = null,
        ?PurchaseInvoiceItemModel $items = null,
        ?PurchaseInvoiceTaxModel $taxes = null,
        ?BaseConnection $db = null
    ) {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->invoices = $invoices ?? new PurchaseInvoiceModel();
        $this->items = $items ?? new PurchaseInvoiceItemModel();
        $this->taxes = $taxes ?? new PurchaseInvoiceTaxModel();
    }

    public function create(array $invoice, array $items, array $taxes): array
    {
        $now = $this->now();
        $invoice['created_at'] = $now;
        $invoice['updated_at'] = $now;

        $this->db->transStart();
        $this->invoices->insert($invoice);
        $invoiceId = (int) $this->invoices->getInsertID();

        $itemRows = [];
        foreach ($items as $item) {
            $itemRows[] = $item + ['invoice_id' => $invoiceId, 'created_at' => $now, 'updated_at' => $now];
        }
        if ($itemRows) {
            $this->items->insertBatch($itemRows);
        }

        $taxRows = [];
        foreach ($taxes as $tax) {
            $taxRows[] = $tax + ['invoice_id' => $invoiceId, 'created_at' => $now, 'updated_at' => $now];
        }
        if ($taxRows) {
            $this->taxes->insertBatch($taxRows);
        }

        $this->db->transComplete();
        return $this->findById($invoiceId);
    }

    public function findById(int $id): ?array
    {
        $invoice = $this->invoices->find($id);
        if (! $invoice) {
            return null;
        }
        $items = $this->items->where('invoice_id', $id)->findAll();
        $taxes = $this->taxes->where('invoice_id', $id)->findAll();
        $invoice = $this->hydrate($invoice);
        $invoice['items'] = array_map(fn ($i) => $this->hydrateItem($i), $items);
        $invoice['taxes'] = array_map(fn ($t) => $this->hydrateTax($t), $taxes);
        return $invoice;
    }

    public function updateStatus(int $id, string $status): bool
    {
        return (bool) $this->invoices->update($id, ['status' => $status, 'updated_at' => $this->now()]);
    }

    public function nextNumber(): string
    {
        $prefix = 'PI-' . date('Ymd');
        $count = $this->invoices->where('invoice_number LIKE', $prefix . '%')->countAllResults();
        return $prefix . '-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['supplier_id'] = isset($row['supplier_id']) ? (int) $row['supplier_id'] : null;
        $row['debit_account_id'] = isset($row['debit_account_id']) ? (int) $row['debit_account_id'] : null;
        $row['credit_account_id'] = isset($row['credit_account_id']) ? (int) $row['credit_account_id'] : null;
        $row['total'] = isset($row['total']) ? (float) $row['total'] : 0.0;
        $row['taxes_total'] = isset($row['taxes_total']) ? (float) $row['taxes_total'] : 0.0;
        $row['grand_total'] = isset($row['grand_total']) ? (float) $row['grand_total'] : 0.0;
        $row['rounding_adjustment'] = isset($row['rounding_adjustment']) ? (float) $row['rounding_adjustment'] : 0.0;
        return $row;
    }

    private function hydrateItem(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['invoice_id'] = isset($row['invoice_id']) ? (int) $row['invoice_id'] : null;
        $row['product_id'] = isset($row['product_id']) ? (int) $row['product_id'] : null;
        $row['quantity'] = isset($row['quantity']) ? (float) $row['quantity'] : 0.0;
        $row['rate'] = isset($row['rate']) ? (float) $row['rate'] : 0.0;
        $row['amount'] = isset($row['amount']) ? (float) $row['amount'] : 0.0;
        return $row;
    }

    private function hydrateTax(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['invoice_id'] = isset($row['invoice_id']) ? (int) $row['invoice_id'] : null;
        $row['template_id'] = isset($row['template_id']) ? (int) $row['template_id'] : null;
        $row['rate_percent'] = isset($row['rate_percent']) ? (float) $row['rate_percent'] : 0.0;
        $row['amount'] = isset($row['amount']) ? (float) $row['amount'] : 0.0;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
