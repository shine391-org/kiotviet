<?php

namespace App\Services\Approvals;

use RuntimeException;

/**
 * Helper hook to require approval for modules.
 *
 * @agent-service: Approval hook
 * @agent-pattern: Guard helper
 * @agent-reusable: MEDIUM
 */
class ApprovalHook
{
    protected ApprovalService $service;

    public function __construct(?ApprovalService $service = null)
    {
        $this->service = $service ?? new ApprovalService();
    }

    /**
     * Require approval for order confirm if rules match.
     * Throws RuntimeException to block transition when pending.
     */
    public function requireOrderApproval(array $order, ?int $actorId = null): void
    {
        $res = $this->service->submitOrder(['order_id' => $order['id'] ?? 0, 'requested_by' => $actorId], $order);
        if (! empty($res['data'])) {
            throw new RuntimeException('Approval required before confirmation');
        }
    }
}
