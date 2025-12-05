<?php

namespace App\Services\Pricing;

use App\Repositories\Pricing\PricingRuleRepository;
use App\Validators\PricingRuleValidator;
use InvalidArgumentException;

/**
 * Pricing rule service.
 *
 * @agent-service: Pricing rules
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class PricingRuleService
{
    protected PricingRuleRepository $repo;
    protected PricingRuleValidator $validator;

    public function __construct(?PricingRuleRepository $repo = null, ?PricingRuleValidator $validator = null)
    {
        $this->repo = $repo ?? new PricingRuleRepository();
        $this->validator = $validator ?? new PricingRuleValidator();
    }

    public function list(array $filters = []): array
    {
        return ['success' => true, 'data' => $this->repo->list($filters)];
    }

    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $row = $this->repo->create($data);
        return ['success' => true, 'data' => $row];
    }

    public function update(int $id, array $input): array
    {
        $data = $this->validator->validateUpdate($input);
        $this->repo->update($id, $data);
        return ['success' => true, 'data' => $this->repo->find($id)];
    }

    /**
     * Find the highest priority rule for given context.
     */
    public function findApplicableRule(array $context): ?array
    {
        $rules = $this->repo->list(['is_active' => 1]);
        $date = $context['date'] ?? date('Y-m-d');
        $productId = (int) ($context['product_id'] ?? 0);
        $variantId = $context['variant_id'] ?? null;
        $customerId = $context['customer_id'] ?? null;
        $projectId = $context['project_id'] ?? null;
        $qty = (float) ($context['quantity'] ?? 1);

        $candidates = [];
        foreach ($rules as $rule) {
            if (! $this->matchesDate($rule, $date) || ! $this->matchesCustomerProject($rule, $customerId, $projectId)) {
                continue;
            }
            if (! $this->matchesProduct($rule, $productId, $variantId)) {
                continue;
            }
            if ($qty + 0.0001 < (float) ($rule['min_qty'] ?? 0)) {
                continue;
            }
            $candidates[] = $rule;
        }
        if (empty($candidates)) {
            return null;
        }
        usort($candidates, static function ($a, $b) {
            return ($a['priority'] <=> $b['priority']) ?: ($a['id'] <=> $b['id']);
        });
        return $candidates[0];
    }

    private function matchesDate(array $rule, string $date): bool
    {
        if (! empty($rule['start_date']) && $rule['start_date'] > $date) {
            return false;
        }
        if (! empty($rule['end_date']) && $rule['end_date'] < $date) {
            return false;
        }
        return true;
    }

    private function matchesCustomerProject(array $rule, ?int $customerId, ?int $projectId): bool
    {
        if (! empty($rule['customer_id']) && (int) $rule['customer_id'] !== (int) $customerId) {
            return false;
        }
        if (! empty($rule['project_id']) && (int) $rule['project_id'] !== (int) $projectId) {
            return false;
        }
        return true;
    }

    private function matchesProduct(array $rule, int $productId, ?int $variantId): bool
    {
        if (! empty($rule['product_id']) && (int) $rule['product_id'] !== $productId) {
            return false;
        }
        if (isset($rule['variant_id']) && $rule['variant_id'] !== null) {
            return (int) $rule['variant_id'] === (int) $variantId;
        }
        return true;
    }
}
