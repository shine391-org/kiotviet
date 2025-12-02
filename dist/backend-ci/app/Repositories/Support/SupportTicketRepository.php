<?php

namespace App\Repositories\Support;

use App\Models\SupportTicketModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Support tickets
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class SupportTicketRepository
{
    protected SupportTicketModel $tickets;
    protected BaseConnection $db;

    public function __construct(?SupportTicketModel $tickets = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->tickets = $tickets ?? new SupportTicketModel();
    }

    public function create(array $data): array
    {
        $now = $this->now();
        $payload = $data + ['created_at' => $now, 'updated_at' => $now];
        $this->tickets->insert($payload);
        $payload['id'] = (int) $this->tickets->getInsertID();
        return $this->hydrate($payload);
    }

    public function update(int $id, array $data): ?array
    {
        $data['updated_at'] = $this->now();
        $this->tickets->update($id, $data);
        return $this->findById($id);
    }

    public function updateStatus(int $id, string $status): bool
    {
        return (bool) $this->tickets->update($id, ['status' => $status, 'updated_at' => $this->now()]);
    }

    public function assign(int $id, ?int $assignedTo): bool
    {
        return (bool) $this->tickets->update($id, ['assigned_to' => $assignedTo, 'updated_at' => $this->now()]);
    }

    public function findById(int $id): ?array
    {
        $row = $this->tickets->find($id);
        return $row ? $this->hydrate($row) : null;
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['customer_id'] = array_key_exists('customer_id', $row) && $row['customer_id'] !== null ? (int) $row['customer_id'] : null;
        $row['lead_id'] = array_key_exists('lead_id', $row) && $row['lead_id'] !== null ? (int) $row['lead_id'] : null;
        $row['assigned_to'] = array_key_exists('assigned_to', $row) && $row['assigned_to'] !== null ? (int) $row['assigned_to'] : null;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
