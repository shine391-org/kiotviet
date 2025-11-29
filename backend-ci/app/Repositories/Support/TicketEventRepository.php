<?php

namespace App\Repositories\Support;

use App\Models\TicketEventModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Ticket events
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class TicketEventRepository
{
    protected TicketEventModel $events;
    protected BaseConnection $db;

    public function __construct(?TicketEventModel $events = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->events = $events ?? new TicketEventModel();
    }

    public function create(array $data): array
    {
        $payload = $data + ['created_at' => $this->now()];
        $this->events->insert($payload);
        $payload['id'] = (int) $this->events->getInsertID();
        return $this->hydrate($payload);
    }

    public function findByTicket(int $ticketId): array
    {
        $rows = $this->events->where('ticket_id', $ticketId)->orderBy('id', 'ASC')->findAll();
        return array_map(fn ($row) => $this->hydrate($row), $rows);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['ticket_id'] = isset($row['ticket_id']) ? (int) $row['ticket_id'] : null;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
