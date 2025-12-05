<?php

namespace App\Services\Taxes;

use App\Repositories\Taxes\TaxTemplateRepository;
use App\Repositories\Taxes\TaxTemplateItemRepository;
use App\Validators\TaxTemplateValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * @agent-service: Tax templates
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class TaxTemplateService
{
    protected TaxTemplateRepository $templates;
    protected TaxTemplateItemRepository $items;
    protected TaxTemplateValidator $validator;

    public function __construct(
        ?TaxTemplateRepository $templates = null,
        ?TaxTemplateItemRepository $items = null,
        ?TaxTemplateValidator $validator = null
    ) {
        $this->templates = $templates ?? new TaxTemplateRepository();
        $this->items = $items ?? new TaxTemplateItemRepository();
        $this->validator = $validator ?? new TaxTemplateValidator();
    }

    /**
     * Create template with items.
     *
     * @agent-use: POST /api/tax-templates
     */
    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $items = $data['items'];
        unset($data['items']);
        $template = $this->templates->create($data);
        $this->items->saveItems($template['id'], $items);
        $template['items'] = $this->items->findByTemplate($template['id']);
        return ['success' => true, 'data' => $template];
    }

    /**
     * Show template.
     */
    public function get(int $id): array
    {
        $tpl = $this->templates->findById($id);
        if (! $tpl) {
            throw new RuntimeException('Template not found');
        }
        $tpl['items'] = $this->items->findByTemplate($id);
        return ['success' => true, 'data' => $tpl];
    }

    /**
     * Apply template to amount.
     *
     * @agent-use: Compute taxes (inclusive/exclusive)
     */
    public function apply(int $templateId, float $baseAmount): array
    {
        $tpl = $this->templates->findById($templateId);
        if (! $tpl) {
            throw new RuntimeException('Template not found');
        }
        $items = $this->items->findByTemplate($templateId);
        if (empty($items)) {
            $items = [[
                'tax_name' => $tpl['name'],
                'rate_percent' => $tpl['rate_percent'] ?? 0,
                'charge_type' => 'on_net_total',
            ]];
        }
        $taxTotal = 0.0;
        foreach ($items as $i) {
            $taxTotal += round($baseAmount * ($i['rate_percent'] / 100), 2);
        }
        if (! empty($tpl['is_inclusive'])) {
            $taxTotal = 0.0;
        }
        $grand = $tpl['is_inclusive'] ? $baseAmount : $baseAmount + $taxTotal;
        return [
            'tax_total' => round($taxTotal, 2),
            'grand_total' => round($grand, 2),
        ];
    }
}
