<?php

namespace App\Services\Support;

use App\Repositories\Support\SupportTicketRepository;
use App\Repositories\Support\TicketEventRepository;
use App\Validators\SupportTicketValidator;
use RuntimeException;

/**
 * @agent-service: Support tickets
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class SupportTicketService
{
    protected SupportTicketRepository $tickets;
    protected TicketEventRepository $events;
    protected SupportTicketValidator $validator;

    public function __construct(
        ?SupportTicketRepository $tickets = null,
        ?TicketEventRepository $events = null,
        ?SupportTicketValidator $validator = null
    ) {
        $this->tickets = $tickets ?? new SupportTicketRepository();
        $this->events = $events ?? new TicketEventRepository();
        $this->validator = $validator ?? new SupportTicketValidator();
    }

    /**
     * Create ticket.
     *
     * @agent-use: Service entry for POST /api/support-tickets
     * @agent-pattern: Validate -> repo create -> event log
     */
    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $ticket = $this->tickets->create($data);
        $this->logEvent($ticket['id'], 'created', null, $ticket['status']);
        $this->notifyStub($ticket, 'created');
        return ['success' => true, 'data' => $ticket];
    }

    /**
     * Update ticket (non-status fields).
     *
     * @agent-use: Service entry for PUT /api/support-tickets/{id}
     * @agent-pattern: Validate -> repo update
     */
    public function update(int $id, array $input): array
    {
        $ticket = $this->tickets->findById($id);
        if (! $ticket) {
            throw new RuntimeException('Ticket not found');
        }
        $data = $this->validator->validateUpdate($input);
        if ($data === []) {
            return ['success' => true, 'data' => $ticket];
        }
        $updated = $this->tickets->update($id, $data);
        return ['success' => true, 'data' => $updated];
    }

    /**
     * Change status.
     *
     * @agent-use: Service entry for POST /api/support-tickets/{id}/status
     * @agent-pattern: Status transition + event
     */
    public function changeStatus(int $id, string $status): array
    {
        $ticket = $this->tickets->findById($id);
        if (! $ticket) {
            throw new RuntimeException('Ticket not found');
        }
        $newStatus = $this->validator->validateStatus($status);
        $this->tickets->updateStatus($id, $newStatus);
        $this->logEvent($id, 'status_change', $ticket['status'], $newStatus);
        $this->notifyStub($ticket, 'status_change');
        return ['success' => true, 'data' => $this->tickets->findById($id)];
    }

    /**
     * Assign ticket.
     *
     * @agent-use: Service entry for POST /api/support-tickets/{id}/assign
     * @agent-pattern: Assignment + event
     */
    public function assign(int $id, $assigneeId): array
    {
        $ticket = $this->tickets->findById($id);
        if (! $ticket) {
            throw new RuntimeException('Ticket not found');
        }
        $assignee = $this->validator->validateAssignment($assigneeId);
        $this->tickets->assign($id, $assignee);
        $this->logEvent($id, 'assigned', null, null, $assignee);
        $this->notifyStub($ticket, 'assigned');
        return ['success' => true, 'data' => $this->tickets->findById($id)];
    }

    /**
     * Get ticket.
     *
     * @agent-use: Service entry for GET /api/support-tickets/{id}
     * @agent-pattern: Repository fetch
     */
    public function get(int $id): array
    {
        $ticket = $this->tickets->findById($id);
        if (! $ticket) {
            throw new RuntimeException('Ticket not found');
        }
        return ['success' => true, 'data' => $ticket];
    }

    private function logEvent(int $ticketId, string $type, ?string $fromStatus = null, ?string $toStatus = null, ?int $assignee = null): void
    {
        $desc = match ($type) {
            'assigned' => $assignee ? 'Assigned to user ' . $assignee : 'Unassigned',
            'status_change' => 'Status changed',
            default => 'Ticket ' . $type,
        };

        $this->events->create([
            'ticket_id' => $ticketId,
            'event_type' => $type,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'description' => $desc,
        ]);
    }

    /**
     * Notification hook stub for future integration.
     *
     * @agent-use: Extend to notify via email/webhook later
     */
    private function notifyStub(array $ticket, string $event): void
    {
        // Stub: integrate NotificationService in future
    }
}
