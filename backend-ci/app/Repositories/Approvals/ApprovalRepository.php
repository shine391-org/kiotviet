<?php

namespace App\Repositories\Approvals;

use App\Models\ApprovalModel;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;

/**
 * Approval instances.
 *
 * @agent-repository: Approvals
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class ApprovalRepository
{
    protected ApprovalModel $approvals;
    public BaseConnection $db;

    public function __construct(?ApprovalModel $approvals = null, ?BaseConnection $db = null)
    {
        $this->approvals = $approvals ?? new ApprovalModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    public function find(int $id): ?array
    {
        $row = $this->approvals->find($id);
        return $row ?: null;
    }

    public function findPendingForEntity(string $entityType, int $entityId): ?array
    {
        $row = $this->db->table('approvals')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('status', 'pending')
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();
        return $row ?: null;
    }

    public function create(array $data): array
    {
        $now = date('Y-m-d H:i:s');
        $payload = $data + ['requested_at' => $now, 'updated_at' => $now];
        $this->db->table('approvals')->insert($payload);
        $payload['id'] = (int) $this->db->insertID();
        return $payload;
    }

    /**
     * Lock approval row for update.
     */
    public function lock(int $id): ?array
    {
        $table = $this->db->prefixTable('approvals');
        $sql = "SELECT * FROM {$table} WHERE id = ?";
        if (strtolower($this->db->DBDriver) !== 'sqlite3') {
            $sql .= " FOR UPDATE";
        }
        $row = $this->db->query($sql, [$id])->getRowArray();
        return $row ?: null;
    }

    public function updateStatus(int $id, string $status, array $extra = []): void
    {
        $payload = $extra + ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        $this->db->table('approvals')->where('id', $id)->update($payload);
    }

    public function updateIndex(int $id, int $nextIndex, ?int $nextApproverId): void
    {
        $this->db->table('approvals')->where('id', $id)->update([
            'current_index' => $nextIndex,
            'current_approver_id' => $nextApproverId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
