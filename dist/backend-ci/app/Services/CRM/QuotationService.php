<?php

namespace App\Services\CRM;

use App\Repositories\CRM\QuotationRepository;
use App\Validators\QuotationValidator;
use App\Services\Pricing\PricingService;
use RuntimeException;

/**
 * @agent-service: Quotation
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class QuotationService
{
    protected QuotationRepository $repo;
    protected QuotationValidator $validator;
    protected PricingService $pricing;
    protected QuotationNumberGenerator $numberGen;

    public function __construct(
        ?QuotationRepository $repo = null,
        ?QuotationValidator $validator = null,
        ?PricingService $pricing = null,
        ?QuotationNumberGenerator $numberGen = null
    ) {
        $this->repo = $repo ?? new QuotationRepository();
        $this->validator = $validator ?? new QuotationValidator();
        $this->pricing = $pricing ?? new PricingService();
        $this->numberGen = $numberGen ?? new QuotationNumberGenerator();
    }

    public function create(array $input): array
    {
        $data = $this->validator->validate($input);
        $items = $this->applyPricing($data['items']);
        $totals = $this->totals($items);

        $payload = $data;
        unset($payload['items']);
        $payload['quote_number'] = $this->numberGen->generate();
        $payload['subtotal'] = $totals['subtotal'];
        $payload['discount_total'] = 0;
        $payload['tax_total'] = 0;
        $payload['total'] = $totals['subtotal'];

        $quote = $this->repo->create($payload, $items);
        return ['success' => true, 'data' => $quote];
    }

    public function createFromOpportunity(array $opportunity): array
    {
        if (empty($opportunity['id'])) {
            throw new RuntimeException('Opportunity required');
        }
        $items = $opportunity['items'] ?? [];
        $payload = [
            'opportunity_id' => $opportunity['id'],
            'customer_id' => $opportunity['customer_id'] ?? null,
            'lead_id' => $opportunity['lead_id'] ?? null,
            'items' => array_map(fn ($item) => [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ], $items),
        ];
        return $this->create($payload);
    }

    public function cancel(int $id): array
    {
        $quote = $this->repo->findById($id);
        if (! $quote) {
            throw new RuntimeException('Quotation not found');
        }
        $this->repo->updateStatus($id, 'cancelled');
        return ['success' => true, 'data' => $this->repo->findById($id)];
    }

    public function approve(int $id): array
    {
        $quote = $this->repo->findById($id);
        if (! $quote) {
            throw new RuntimeException('Quotation not found');
        }
        $this->repo->updateStatus($id, 'approved');
        return ['success' => true, 'data' => $this->repo->findById($id)];
    }

    private function applyPricing(array $items): array
    {
        $priced = [];
        foreach ($items as $item) {
            $res = $this->pricing->getPrice([
                'product_id' => $item['product_id'],
                'variant_id' => null,
                'customer_id' => $item['customer_id'] ?? null,
                'project_id' => null,
                'quantity' => $item['quantity'],
                'order_date' => date('Y-m-d'),
            ]);
            $priced[] = $item + ['price' => (float) ($res['final_price'] ?? 0)];
        }
        return $priced;
    }

    private function totals(array $items): array
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        return ['subtotal' => round($subtotal, 2)];
    }
}
