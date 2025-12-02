<?php

namespace App\Services\Approvals;

use App\Repositories\Approvals\ApprovalRuleRepository;
use App\Validators\ApprovalRuleValidator;

/**
 * Approval rule business logic.
 *
 * @agent-service: Approval rules
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class ApprovalRuleService
{
    protected ApprovalRuleRepository $rules;
    protected ApprovalRuleValidator $validator;

    public function __construct(?ApprovalRuleRepository $rules = null, ?ApprovalRuleValidator $validator = null)
    {
        $this->rules = $rules ?? new ApprovalRuleRepository();
        $this->validator = $validator ?? new ApprovalRuleValidator();
    }

    /** List rules. @agent-use: GET /api/approval-rules */
    public function list(array $filters = []): array
    {
        return ['success' => true, 'data' => $this->rules->list($filters)];
    }

    /** Create rule. */
    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $row = $this->rules->create($this->encodeApprovers($data));
        return ['success' => true, 'data' => $row];
    }

    /** Update rule. */
    public function update(int $id, array $input): array
    {
        $data = $this->validator->validateUpdate($input);
        $this->rules->update($id, $this->encodeApprovers($data));
        return ['success' => true, 'data' => $this->rules->find($id)];
    }

    /**
     * Evaluate rules for an order and return approver queue (array of user IDs).
     * @agent-use: Order approval hook
     */
    public function evaluateForOrder(array $order): array
    {
        $rules = $this->rules->list(['is_active' => 1]);
        $queue = [];
        foreach ($rules as $rule) {
            if (! $this->matchRule($rule, $order)) {
                continue;
            }
            $ids = $this->decodeApprovers($rule['approver_ids'] ?? '');
            foreach ($ids as $id) {
                if (! in_array($id, $queue, true)) {
                    $queue[] = $id;
                }
            }
        }
        return $queue;
    }

    private function matchRule(array $rule, array $order): bool
    {
        if (empty($rule['is_active'])) {
            return false;
        }
        $type = $rule['condition_type'] ?? 'amount';
        if ($type === 'amount') {
            $total = (float) ($order['total'] ?? 0);
            return $total >= (float) ($rule['threshold_amount'] ?? 0);
        }
        if ($type === 'customer') {
            $cid = $order['customer_id'] ?? null;
            return $cid && (int) $cid === (int) ($rule['customer_id'] ?? 0);
        }
        if ($type === 'custom') {
            // simple custom: if custom_condition not empty, treat as match
            return ! empty($rule['custom_condition']);
        }
        return false;
    }

    private function encodeApprovers(array $data): array
    {
        if (isset($data['approver_ids']) && is_array($data['approver_ids'])) {
            $data['approver_ids'] = json_encode(array_values($data['approver_ids']));
        }
        return $data;
    }

    private function decodeApprovers($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return array_values(array_unique(array_map('intval', $decoded)));
            }
            $value = array_map('trim', explode(',', $value));
        }
        if (! is_array($value)) {
            return [];
        }
        $ids = array_map(static fn ($v) => (int) $v, $value);
        return array_values(array_unique(array_filter($ids, static fn ($v) => $v > 0)));
    }
}
