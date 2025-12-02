<?php

namespace App\Services\Accounting;

use App\Repositories\Accounting\WithholdingRuleRepository;
use App\Validators\WithholdingValidator;
use RuntimeException;

/**
 * @agent-service: Withholding tax
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class WithholdingService
{
    protected WithholdingRuleRepository $repo;
    protected WithholdingValidator $validator;

    public function __construct(?WithholdingRuleRepository $repo = null, ?WithholdingValidator $validator = null)
    {
        $this->repo = $repo ?? new WithholdingRuleRepository();
        $this->validator = $validator ?? new WithholdingValidator();
    }

    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $rule = $this->repo->create($data);
        return ['success' => true, 'data' => $rule];
    }

    public function apply(int $ruleId, float $baseAmount): array
    {
        $rule = $this->repo->findById($ruleId);
        if (! $rule) {
            throw new RuntimeException('Withholding rule not found');
        }
        if ($baseAmount < $rule['apply_threshold']) {
            return ['amount' => 0.0];
        }
        $amount = round($baseAmount * ($rule['rate_percent'] / 100), 2);
        return ['amount' => $amount];
    }
}
