<?php

namespace App\Services\Approvals;

use App\Repositories\Approvals\ApprovalActionRepository;
use App\Repositories\Approvals\ApprovalRepository;
use App\Services\Approvals\ApprovalRuleService;
use App\Repositories\Orders\OrderRepository;
use App\Validators\ApprovalRequestValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Approval workflow service.
 *
 * @agent-service: Approvals
 * @agent-pattern: State + side-effects
 * @agent-reusable: MEDIUM
 */
class ApprovalService
{
    protected ApprovalRepository $approvals;
    protected ApprovalActionRepository $actions;
    protected ApprovalRuleService $rules;
    protected ApprovalRequestValidator $validator;
    protected OrderRepository $orders;

    public function __construct(
        ?ApprovalRepository $approvals = null,
        ?ApprovalActionRepository $actions = null,
        ?ApprovalRuleService $rules = null,
        ?ApprovalRequestValidator $validator = null,
        ?OrderRepository $orders = null
    ) {
        $this->approvals = $approvals ?? new ApprovalRepository();
        $this->actions = $actions ?? new ApprovalActionRepository();
        $this->rules = $rules ?? new ApprovalRuleService();
        $this->validator = $validator ?? new ApprovalRequestValidator();
        $this->orders = $orders ?? new OrderRepository();
    }

    /**
     * Load order with items for approval context.
     *
     * @agent-use: Controller submits approval request
     * @agent-pattern: Delegate data access to repository
     */
    public function loadOrderWithItems(int $orderId): array
    {
        if ($orderId <= 0) {
            return [];
        }

        return $this->orders->findById($orderId) ?? [];
    }

    /** Submit approval for order. @agent-use: POST /api/approvals/submit */
    public function submitOrder(array $input, array $order): array
    {
        $payload = $this->validator->validateSubmit($input);
        $existing = $this->approvals->findPendingForEntity('order', (int) $payload['order_id']);
        if ($existing) {
            return ['success' => true, 'data' => $existing, 'message' => 'Approval already pending'];
        }

        $queue = $this->rules->evaluateForOrder($order);
        if (empty($queue)) {
            return ['success' => true, 'data' => null, 'message' => 'No approval required'];
        }

        $approval = $this->approvals->create([
            'entity_type' => 'order',
            'entity_id' => (int) $payload['order_id'],
            'order_id' => (int) $payload['order_id'],
            'status' => 'pending',
            'approver_queue' => json_encode($queue),
            'current_index' => 0,
            'current_approver_id' => $queue[0],
            'requested_by' => $payload['requested_by'] ?? null,
        ]);
        $this->actions->log([
            'approval_id' => $approval['id'],
            'action' => 'submitted',
            'actor_id' => $payload['requested_by'] ?? null,
            'notes' => null,
        ]);

        return ['success' => true, 'data' => $approval, 'message' => 'Approval created'];
    }

    /** Approve current step. @agent-use: POST /api/approvals/{id}/approve */
    public function approve(int $id, array $input): array
    {
        $payload = $this->validator->validateApprove($input);
        $db = $this->approvals->db;
        $db->transBegin();
        $approval = $this->approvals->lock($id);
        if (! $approval) {
            $db->transRollback();
            throw new RuntimeException('Approval not found');
        }
        if ($approval['status'] !== 'pending') {
            $db->transRollback();
            throw new InvalidArgumentException('Approval is not pending');
        }

        $queue = $this->decodeQueue($approval['approver_queue'] ?? '');
        $idx = (int) ($approval['current_index'] ?? 0);
        $currentApprover = $queue[$idx] ?? null;
        if ($currentApprover !== (int) $payload['actor_id']) {
            $db->transRollback();
            throw new InvalidArgumentException('Not current approver');
        }

        $this->actions->log([
            'approval_id' => $id,
            'action' => 'approved',
            'actor_id' => $payload['actor_id'],
            'notes' => $payload['notes'] ?? null,
        ]);

        $nextIndex = $idx + 1;
        if (! isset($queue[$nextIndex])) {
            $this->approvals->updateStatus($id, 'approved');
            $db->transCommit();
            $approval['status'] = 'approved';
            return ['success' => true, 'data' => $approval];
        }

        $this->approvals->updateIndex($id, $nextIndex, $queue[$nextIndex]);
        $db->transCommit();
        $approval['current_index'] = $nextIndex;
        $approval['current_approver_id'] = $queue[$nextIndex];
        return ['success' => true, 'data' => $approval];
    }

    /** Reject approval. @agent-use: POST /api/approvals/{id}/reject */
    public function reject(int $id, array $input): array
    {
        $payload = $this->validator->validateReject($input);
        $db = $this->approvals->db;
        $db->transBegin();
        $approval = $this->approvals->lock($id);
        if (! $approval) {
            $db->transRollback();
            throw new RuntimeException('Approval not found');
        }
        if ($approval['status'] !== 'pending') {
            $db->transRollback();
            throw new InvalidArgumentException('Approval is not pending');
        }

        $queue = $this->decodeQueue($approval['approver_queue'] ?? '');
        $idx = (int) ($approval['current_index'] ?? 0);
        $currentApprover = $queue[$idx] ?? null;
        if ($currentApprover !== (int) $payload['actor_id']) {
            $db->transRollback();
            throw new InvalidArgumentException('Not current approver');
        }

        $this->actions->log([
            'approval_id' => $id,
            'action' => 'rejected',
            'actor_id' => $payload['actor_id'],
            'notes' => $payload['notes'] ?? null,
        ]);
        $this->approvals->updateStatus($id, 'rejected');
        $db->transCommit();

        $approval['status'] = 'rejected';
        return ['success' => true, 'data' => $approval];
    }

    private function decodeQueue($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return array_values(array_map('intval', $decoded));
            }
            $value = array_map('trim', explode(',', $value));
        }
        if (! is_array($value)) {
            return [];
        }
        return array_values(array_map('intval', $value));
    }
}
