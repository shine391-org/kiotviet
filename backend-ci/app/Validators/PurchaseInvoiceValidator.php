<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate purchase invoices.
 *
 * @agent-validator: PurchaseInvoice
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class PurchaseInvoiceValidator
{
    public function validateCreate(array $input): array
    {
        $supplierId = isset($input['supplier_id']) ? (int) $input['supplier_id'] : 0;
        if ($supplierId <= 0) {
            throw new InvalidArgumentException('supplier_id is required');
        }

        $postingDate = $this->normalizeDate($input['posting_date'] ?? date('Y-m-d'));
        $dueDate = isset($input['due_date']) ? $this->normalizeDate($input['due_date']) : null;
        if ($dueDate && $dueDate < $postingDate) {
            throw new InvalidArgumentException('due_date must be after posting_date');
        }

        $items = $input['items'] ?? [];
        if (! is_array($items) || empty($items)) {
            throw new InvalidArgumentException('items are required');
        }

        $normalizedItems = [];
        foreach ($items as $item) {
            $qty = isset($item['quantity']) ? (float) $item['quantity'] : 0;
            $rate = isset($item['rate']) ? (float) $item['rate'] : 0;
            if ($qty <= 0 || $rate < 0) {
                throw new InvalidArgumentException('item quantity/rate invalid');
            }
            $normalizedItems[] = [
                'product_id' => isset($item['product_id']) ? (int) $item['product_id'] : null,
                'description' => $item['description'] ?? null,
                'quantity' => $qty,
                'rate' => $rate,
                'amount' => round($qty * $rate, 2),
            ];
        }

        $taxes = $input['taxes'] ?? [];
        if ($taxes !== null && ! is_array($taxes)) {
            throw new InvalidArgumentException('taxes must be array');
        }
        $normalizedTaxes = [];
        foreach ($taxes as $tax) {
            $rate = isset($tax['rate_percent']) ? (float) $tax['rate_percent'] : 0;
            $name = trim((string) ($tax['tax_name'] ?? ''));
            if ($name === '' || $rate < 0) {
                throw new InvalidArgumentException('tax name/rate invalid');
            }
            $normalizedTaxes[] = [
                'tax_name' => $name,
                'rate_percent' => $rate,
                'template_id' => isset($tax['template_id']) ? (int) $tax['template_id'] : null,
            ];
        }

        $rounding = isset($input['rounding_adjustment']) ? (float) $input['rounding_adjustment'] : 0;

        $debitAccountId = isset($input['debit_account_id']) ? (int) $input['debit_account_id'] : 0;
        $creditAccountId = isset($input['credit_account_id']) ? (int) $input['credit_account_id'] : 0;
        if ($debitAccountId <= 0 || $creditAccountId <= 0) {
            throw new InvalidArgumentException('debit_account_id and credit_account_id are required');
        }

        $currency = isset($input['currency']) ? strtoupper(trim((string) $input['currency'])) : 'VND';
        $exchangeRate = isset($input['exchange_rate']) ? (float) $input['exchange_rate'] : 1;
        if ($exchangeRate <= 0) {
            throw new InvalidArgumentException('exchange_rate must be positive');
        }

        return [
            'supplier_id' => $supplierId,
            'posting_date' => $postingDate->format('Y-m-d'),
            'due_date' => $dueDate?->format('Y-m-d'),
            'items' => $normalizedItems,
            'taxes' => $normalizedTaxes,
            'rounding_adjustment' => $rounding,
            'debit_account_id' => $debitAccountId,
            'credit_account_id' => $creditAccountId,
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'status' => $input['status'] ?? 'draft',
            'remarks' => isset($input['remarks']) ? trim((string) $input['remarks']) : null,
        ];
    }

    private function normalizeDate($date): \DateTimeInterface
    {
        if (! $date) {
            throw new InvalidArgumentException('date is required');
        }
        try {
            return new \DateTime($date);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException('invalid date');
        }
    }
}
