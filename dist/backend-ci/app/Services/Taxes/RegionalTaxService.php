<?php

namespace App\Services\Taxes;

use App\Repositories\Taxes\RegionalTaxRuleRepository;
use App\Repositories\Taxes\EInvoiceLogRepository;
use App\Validators\RegionalTaxValidator;
use RuntimeException;

/**
 * Regional tax application service.
 *
 * @agent-service: Regional tax
 * @agent-pattern: Rule apply + e-invoice stub
 * @agent-reusable: MEDIUM
 */
class RegionalTaxService
{
    protected RegionalTaxRuleRepository $rules;
    protected EInvoiceLogRepository $eInvoices;
    protected RegionalTaxValidator $validator;

    public function __construct(
        ?RegionalTaxRuleRepository $rules = null,
        ?EInvoiceLogRepository $eInvoices = null,
        ?RegionalTaxValidator $validator = null
    ) {
        $this->rules = $rules ?? new RegionalTaxRuleRepository();
        $this->eInvoices = $eInvoices ?? new EInvoiceLogRepository();
        $this->validator = $validator ?? new RegionalTaxValidator();
    }

    /** @agent-use: POST /api/taxes/regional/rules */
    public function setRule(array $input): array
    {
        $data = $this->validator->validateRule($input);
        $rule = $this->rules->upsert($data);
        return ['success' => true, 'data' => $rule];
    }

    /** @agent-use: POST /api/taxes/regional/preview */
    public function preview(array $input): array
    {
        $data = $this->validator->validatePreview($input);
        $rule = $this->rules->findByCountry($data['country']);
        if (! $rule) {
            throw new RuntimeException('No rule for country');
        }
        $gstRate = (float) ($rule['rule_json']['gst_rate'] ?? ($rule['rule_json']['vat_rate'] ?? 0));
        $surcharge = (float) ($rule['rule_json']['surcharge_rate'] ?? 0);
        $lines = [];
        $totalTax = 0;
        foreach ($data['lines'] as $line) {
            $rate = $line['tax_rate'] > 0 ? $line['tax_rate'] : $gstRate;
            $tax = round($line['amount'] * $rate / 100, 2);
            $tax += round($line['amount'] * $surcharge / 100, 2);
            $totalTax += $tax;
            $lines[] = $line + ['applied_rate' => $rate, 'tax_amount' => $tax];
        }
        return ['success' => true, 'data' => ['lines' => $lines, 'total_tax' => $totalTax]];
    }

    /** @agent-use: POST /api/taxes/regional/e-invoice */
    public function logEInvoice(array $payload): array
    {
        $row = $this->eInvoices->log([
            'invoice_id' => $payload['invoice_id'] ?? null,
            'status' => 'queued',
            'payload' => json_encode($payload),
        ]);
        return ['success' => true, 'data' => $row];
    }
}
