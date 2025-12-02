<?php

namespace App\Services\POS;

use App\Repositories\Taxes\TaxChargeRepository;
use App\Repositories\Taxes\TaxTemplateRepository;
use InvalidArgumentException;

/**
 * @agent-service: POS tax & rounding
 * @agent-pattern: Calculation service
 * @agent-reusable: MEDIUM
 */
class POSTaxService
{
    protected TaxTemplateRepository $templates;
    protected TaxChargeRepository $charges;

    public function __construct(?TaxTemplateRepository $templates = null, ?TaxChargeRepository $charges = null)
    {
        $this->templates = $templates ?? new TaxTemplateRepository();
        $this->charges = $charges ?? new TaxChargeRepository();
    }

    /**
     * Apply tax template to base amount.
     *
     * @agent-use: POS checkout
     */
    public function apply(?int $templateId, float $baseAmount): array
    {
        if (! $templateId) {
            return ['tax_total' => 0.0, 'rounding_adjustment' => 0.0, 'grand_total' => round($baseAmount, 2)];
        }
        $template = $this->templates->findById($templateId);
        if (! $template) {
            throw new InvalidArgumentException('Tax template not found');
        }
        $charges = $this->charges->listByTemplate($templateId);
        $tax = $this->calculateTax($baseAmount, $template, $charges);
        $grand = $baseAmount + $tax;
        $rounded = $this->applyRounding($grand, $template['rounding_rule'] ?? 'nearest');
        return [
            'tax_total' => round($tax, 2),
            'rounding_adjustment' => round($rounded - $grand, 2),
            'grand_total' => round($rounded, 2),
        ];
    }

    private function calculateTax(float $base, array $template, array $charges): float
    {
        $rate = (float) ($template['rate_percent'] ?? 0);
        $tax = $base * ($rate / 100);
        foreach ($charges as $charge) {
            $tax += $base * ((float) ($charge['rate_percent'] ?? 0) / 100);
        }
        return $tax;
    }

    private function applyRounding(float $amount, string $rule): float
    {
        return match ($rule) {
            'up' => ceil($amount),
            'down' => floor($amount),
            default => round($amount, 0),
        };
    }
}
