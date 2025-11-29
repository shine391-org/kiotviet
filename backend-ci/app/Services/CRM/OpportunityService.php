<?php

namespace App\Services\CRM;

use App\Repositories\CRM\OpportunityRepository;
use App\Validators\OpportunityValidator;
use App\Services\Pricing\PricingService;
use RuntimeException;

/**
 * @agent-service: Opportunity
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class OpportunityService
{
    protected OpportunityRepository $repo;
    protected OpportunityValidator $validator;
    protected PricingService $pricing;

    public function __construct(
        ?OpportunityRepository $repo = null,
        ?OpportunityValidator $validator = null,
        ?PricingService $pricing = null
    ) {
        $this->repo = $repo ?? new OpportunityRepository();
        $this->validator = $validator ?? new OpportunityValidator();
        $this->pricing = $pricing ?? new PricingService();
    }

    public function create(array $input): array
    {
        $data = $this->validator->validate($input);
        $items = $this->applyPricing($data['items']);
        $expected = array_sum(array_map(fn ($item) => $item['price'] * $item['quantity'], $items));
        $payload = $data;
        unset($payload['items']);
        $payload['expected_value'] = $expected;
        $opportunity = $this->repo->create($payload, $items);
        return ['success' => true, 'data' => $opportunity];
    }

    public function updateStage(int $id, string $stage, ?int $probability = null): array
    {
        $opp = $this->repo->findById($id);
        if (! $opp) {
            throw new RuntimeException('Opportunity not found');
        }
        $prob = $probability ?? $opp['probability'] ?? 0;
        $this->repo->updateOpportunity($id, ['stage' => $stage, 'probability' => $prob]);
        return ['success' => true, 'data' => $this->repo->findById($id)];
    }

    public function find(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    private function applyPricing(array $items): array
    {
        $priced = [];
        foreach ($items as $item) {
            $res = $this->pricing->getPrice([
                'product_id' => $item['product_id'],
                'variant_id' => null,
                'customer_id' => null,
                'project_id' => null,
                'quantity' => $item['quantity'],
                'order_date' => date('Y-m-d'),
            ]);
            $priced[] = $item + ['price' => (float) ($res['final_price'] ?? $item['price'] ?? 0)];
        }
        return $priced;
    }
}
