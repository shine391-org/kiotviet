<?php

namespace App\Repositories\Support;

use App\Models\TicketCommunicationModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Ticket communications
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class CommunicationRepository
{
    protected TicketCommunicationModel $communications;
    protected BaseConnection $db;

    public function __construct(?TicketCommunicationModel $communications = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->communications = $communications ?? new TicketCommunicationModel();
    }

    public function create(array $data): array
    {
        $now = $this->now();
        $payload = $data + ['created_at' => $now, 'updated_at' => $now];
        $this->communications->insert($payload);
        $payload['id'] = (int) $this->communications->getInsertID();
        return $this->hydrate($payload);
    }

    public function findByTicket(int $ticketId): array
    {
        $rows = $this->communications->where('ticket_id', $ticketId)->orderBy('created_at', 'ASC')->findAll();
        return array_map(fn ($row) => $this->hydrate($row), $rows);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['ticket_id'] = isset($row['ticket_id']) ? (int) $row['ticket_id'] : null;
        $row['created_by'] = isset($row['created_by']) ? (int) $row['created_by'] : null;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
