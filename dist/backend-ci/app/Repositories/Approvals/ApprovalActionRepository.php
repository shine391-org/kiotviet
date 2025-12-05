<?php

namespace App\Repositories\Approvals;

use App\Models\ApprovalActionModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Approval action logs.
 *
 * @agent-repository: Approval actions
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class ApprovalActionRepository
{
    protected ApprovalActionModel $actions;
    protected BaseConnection $db;

    public function __construct(?ApprovalActionModel $actions = null, ?BaseConnection $db = null)
    {
        $this->actions = $actions ?? new ApprovalActionModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    public function log(array $data): array
    {
        $payload = $data + ['created_at' => date('Y-m-d H:i:s')];
        $this->actions->insert($payload);
        $payload['id'] = (int) $this->actions->getInsertID();
        return $payload;
    }

    public function byApproval(int $approvalId): array
    {
        return $this->actions->where('approval_id', $approvalId)->orderBy('id', 'ASC')->findAll();
    }
}
