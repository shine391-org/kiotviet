<?php

namespace App\Services\Invoices;

use App\Repositories\Invoices\InvoiceRepository;
use App\Transformers\InvoiceTransformer;
use App\Validators\InvoiceValidator;
use App\Models\CustomerModel;
use App\Models\BranchModel;
use App\Services\Webhooks\WebhookDispatcher;
use InvalidArgumentException;
use RuntimeException;

/**
 * Invoice business logic.
 *
 * @agent-service: Invoices
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class InvoiceService
{
    protected InvoiceRepository $repo;
    protected InvoiceValidator $validator;
    protected InvoiceTransformer $transformer;
    protected VATCalculator $vatCalc;
    protected InvoicePDFGenerator $pdf;
    protected ?WebhookDispatcher $webhooks;
    protected CustomerModel $customers;
    protected BranchModel $branches;

    public function __construct(
        ?InvoiceRepository $repo = null,
        ?InvoiceValidator $validator = null,
        ?InvoiceTransformer $transformer = null,
        ?VATCalculator $vatCalc = null,
        ?InvoicePDFGenerator $pdf = null,
        ?WebhookDispatcher $webhooks = null
    ) {
        $this->repo = $repo ?? new InvoiceRepository();
        $this->validator = $validator ?? new InvoiceValidator();
        $this->transformer = $transformer ?? new InvoiceTransformer();
        $this->vatCalc = $vatCalc ?? new VATCalculator();
        $this->pdf = $pdf ?? new InvoicePDFGenerator();
        $this->webhooks = $webhooks;
        $this->customers = new CustomerModel();
        $this->branches = new BranchModel();
    }

    /** List invoices. @agent-use: GET /api/invoices */
    public function list(array $filters): array
    {
        $validated = $this->validator->validateListFilters($filters);
        $rows = $this->repo->findAll($validated);
        $total = $this->repo->count($validated);
        $totals = $this->repo->totals($validated);
        $pageTotals = $this->aggregatePageTotals($rows);
        return [
            'success' => true,
            'data' => $this->transformer->transformList($rows),
            'pagination' => $this->pagination($validated, $total),
            'totals' => $totals,
            'pageTotals' => $pageTotals,
        ];
    }

    /** Get single invoice. @agent-use: GET /api/invoices/{id} */
    public function get(int $id): array
    {
        $invoice = $this->repo->findById($id);
        if (! $invoice) {
            throw new RuntimeException('Invoice not found');
        }
        return ['success' => true, 'data' => $this->transformer->transform($invoice)];
    }

    /** Create invoice + links. @agent-use: POST /api/invoices */
    public function create(array $payload): array
    {
        $validated = $this->validator->validateCreate($payload);
        $orderIds = $validated['order_ids'];

        // Check order already invoiced
        $existing = $this->repo->findExistingInvoiceForOrders($orderIds);
        if ($existing) {
            throw new InvalidArgumentException('One or more orders already invoiced');
        }

        $orders = $this->repo->ordersByIds($orderIds);
        if (count($orders) !== count($orderIds)) {
            throw new InvalidArgumentException('Some orders not found');
        }
        foreach ($orders as $o) {
            if (($o['status'] ?? '') !== 'completed') {
                throw new InvalidArgumentException('All orders must be completed');
            }
        }

        $this->assertOrdersBelongToBranch($orders, $validated['branch_id']);
        $this->assertCustomerHasTaxCode($validated['customer_id']);

        $subtotal = array_sum(array_map(fn ($o) => (float) $o['total'], $orders));
        $vat = $this->vatCalc->calculate($subtotal, $validated['vat_rate']);
        $number = $this->repo->nextNumber($validated['branch_id'], $validated['issue_date']);
        $snap = $this->buildSnapshotTotals($subtotal, $vat['vat_amount']);

        $invoiceRow = [
            'invoice_number' => $number,
            'customer_id' => $validated['customer_id'],
            'branch_id' => $validated['branch_id'],
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'subtotal' => $subtotal,
            'vat_rate' => $validated['vat_rate'],
            'vat_amount' => $vat['vat_amount'],
            'tax_amount' => $vat['vat_amount'],
            'total' => $vat['total'],
            'goods_total' => $snap['goods_total'],
            'discount_total' => $snap['discount_total'],
            'net_total' => $snap['net_total'],
            'other_fee' => $snap['other_fee'],
            'shipping_fee' => $snap['shipping_fee'],
            'customer_payable' => $snap['customer_payable'],
            'customer_paid' => $snap['customer_paid'],
            'cod_amount' => $snap['cod_amount'],
            'rounding_adjustment' => $snap['rounding_adjustment'],
            'payment_status' => $snap['payment_status'],
            'total_paid' => $snap['total_paid'],
            'notes' => $validated['notes'],
            'created_by' => $validated['created_by'] ?? null,
            'invoice_status' => 'completed',
            'invoice_type' => $validated['invoice_type'] ?? 'standard',
        ];

        $invoice = $this->repo->create($invoiceRow, $orderIds);
        $transformed = $this->transformer->transform($invoice);
        $this->emit('invoice.generated', $transformed);
        return ['success' => true, 'data' => $transformed];
    }

    /**
     * Generate invoice from completed orders.
     *
     * @agent-use: POST /api/invoices/generate
     */
    public function generateFromOrders(array $payload): array
    {
        $orderIds = $payload['order_ids'] ?? [];
        if (empty($orderIds)) {
            throw new InvalidArgumentException('order_ids is required');
        }
        $branchId = (int) ($payload['branch_id'] ?? 0);
        if ($branchId <= 0) {
            throw new InvalidArgumentException('branch_id is required');
        }
        $vatRate = isset($payload['vat_rate']) ? (float) $payload['vat_rate'] : 0.1;
        if ($vatRate < 0 || $vatRate > 1) {
            throw new InvalidArgumentException('vat_rate must be between 0 and 1');
        }

        $orders = $this->repo->ordersByIds($orderIds);
        if (count($orders) !== count($orderIds)) {
            throw new InvalidArgumentException('Some orders not found');
        }

        $customerIds = array_unique(array_column($orders, 'customer_id'));
        if (count($customerIds) > 1) {
            throw new InvalidArgumentException('Orders must belong to the same customer');
        }
        $orderBranchIds = array_unique(array_column($orders, 'branch_id'));
        if (count($orderBranchIds) > 1 || (int) $orderBranchIds[0] !== $branchId) {
            throw new InvalidArgumentException('Orders must belong to the specified branch');
        }
        foreach ($orders as $o) {
            if (($o['status'] ?? '') !== 'completed') {
                throw new InvalidArgumentException('All orders must be completed');
            }
        }
        $this->assertCustomerHasTaxCode((int) $customerIds[0]);

        $existing = $this->repo->findExistingInvoiceForOrders($orderIds);
        if ($existing) {
            throw new InvalidArgumentException('One or more orders already invoiced');
        }

        $subtotal = array_sum(array_column($orders, 'total'));
        $vat = $this->vatCalc->calculate($subtotal, $vatRate);
        $number = $this->repo->nextNumber($branchId, $payload['issue_date'] ?? date('Y-m-d'));
        $customerId = (int) $customerIds[0];
        $snap = $this->buildSnapshotTotals($subtotal, $vat['vat_amount']);

        $invoiceRow = [
            'invoice_number' => $number,
            'customer_id' => $customerId,
            'branch_id' => $branchId,
            'issue_date' => $payload['issue_date'] ?? date('Y-m-d'),
            'due_date' => $payload['due_date'] ?? null,
            'subtotal' => $subtotal,
            'vat_rate' => $vatRate,
            'vat_amount' => $vat['vat_amount'],
            'tax_amount' => $vat['vat_amount'],
            'total' => $vat['total'],
            'goods_total' => $snap['goods_total'],
            'discount_total' => $snap['discount_total'],
            'net_total' => $snap['net_total'],
            'other_fee' => $snap['other_fee'],
            'shipping_fee' => $snap['shipping_fee'],
            'customer_payable' => $snap['customer_payable'],
            'customer_paid' => $snap['customer_paid'],
            'cod_amount' => $snap['cod_amount'],
            'rounding_adjustment' => $snap['rounding_adjustment'],
            'payment_status' => $snap['payment_status'],
            'total_paid' => $snap['total_paid'],
            'notes' => $payload['notes'] ?? null,
            'created_by' => $payload['created_by'] ?? null,
            'invoice_status' => 'completed',
            'invoice_type' => $payload['invoice_type'] ?? 'standard',
        ];

        $invoice = $this->repo->create($invoiceRow, $orderIds);
        $transformed = $this->transformer->transform($invoice);
        $this->emit('invoice.generated', $transformed);
        return ['success' => true, 'data' => $transformed];
    }

    /** Generate PDF and return path. @agent-use: POST /api/invoices/{id}/pdf */
    public function generatePdf(int $id): array
    {
        $invoice = $this->repo->findById($id);
        if (! $invoice) {
            throw new RuntimeException('Invoice not found');
        }
        $path = $this->pdf->generate($invoice);
        $this->repo->updatePdfPath($id, $path);
        $invoice['pdf_path'] = $path;
        return ['success' => true, 'data' => $this->transformer->transform($invoice)];
    }

    private function assertOrdersBelongToBranch(array $orders, int $branchId): void
    {
        foreach ($orders as $o) {
            if (isset($o['branch_id']) && $o['branch_id'] && (int) $o['branch_id'] !== $branchId) {
                throw new InvalidArgumentException('Orders must belong to the same branch');
            }
        }
    }

    private function pagination(array $filters, int $total): array
    {
        $limit = $filters['limit'] ?? 20;
        $page = $filters['page'] ?? 1;
        $totalPages = (int) ceil($total / ($limit ?: 1));
        return [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => $totalPages,
        ];
    }

    private function aggregatePageTotals(array $items): array
    {
        $totals = ['customer_payable' => 0, 'customer_paid' => 0, 'cod_amount' => 0, 'shipping_fee' => 0];
        foreach ($items as $row) {
            $totals['customer_payable'] += (float) ($row['customer_payable'] ?? 0);
            $totals['customer_paid'] += (float) ($row['customer_paid'] ?? 0);
            $totals['cod_amount'] += (float) ($row['cod_amount'] ?? 0);
            $totals['shipping_fee'] += (float) ($row['shipping_fee'] ?? 0);
        }
        return $totals;
    }

    private function emit(string $event, array $payload): void
    {
        if (! $this->webhooks) {
            return;
        }
        try {
            $this->webhooks->dispatch($event, $payload);
        } catch (\Throwable $e) {
            log_message('error', 'Webhook dispatch failed: ' . $e->getMessage());
        }
    }

    private function assertCustomerHasTaxCode(int $customerId): void
    {
        $customer = $this->customers->select('tax_code')->find($customerId);
        if (! $customer || empty($customer['tax_code'])) {
            throw new InvalidArgumentException('Customer tax_code is required to create invoice');
        }
    }

    private function buildSnapshotTotals(float $subtotal, float $vatAmount): array
    {
        $goodsTotal = $subtotal;
        $discount = 0.0;
        $otherFee = 0.0;
        $shipping = 0.0;
        $rounding = 0.0;
        $net = $goodsTotal - $discount + $vatAmount + $otherFee + $shipping + $rounding;
        $customerPaid = 0.0;
        $cod = $net - $customerPaid;
        $paymentStatus = $customerPaid <= 0 ? 'unpaid' : ($customerPaid < $net ? 'partial' : 'paid');
        return [
            'goods_total' => $goodsTotal,
            'discount_total' => $discount,
            'net_total' => $net,
            'tax_amount' => $vatAmount,
            'other_fee' => $otherFee,
            'shipping_fee' => $shipping,
            'customer_payable' => $net,
            'customer_paid' => $customerPaid,
            'cod_amount' => $cod,
            'rounding_adjustment' => $rounding,
            'payment_status' => $paymentStatus,
            'total_paid' => $customerPaid,
        ];
    }
}
