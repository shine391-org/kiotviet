<?php

namespace App\Services\Support;

use App\Repositories\Support\CommunicationRepository;
use App\Repositories\Support\SupportTicketRepository;
use App\Repositories\Support\TicketEventRepository;
use App\Validators\CommunicationValidator;
use RuntimeException;

/**
 * @agent-service: Ticket communications
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class CommunicationService
{
    protected CommunicationRepository $communications;
    protected SupportTicketRepository $tickets;
    protected TicketEventRepository $events;
    protected CommunicationValidator $validator;

    public function __construct(
        ?CommunicationRepository $communications = null,
        ?SupportTicketRepository $tickets = null,
        ?TicketEventRepository $events = null,
        ?CommunicationValidator $validator = null
    ) {
        $this->communications = $communications ?? new CommunicationRepository();
        $this->tickets = $tickets ?? new SupportTicketRepository();
        $this->events = $events ?? new TicketEventRepository();
        $this->validator = $validator ?? new CommunicationValidator();
    }

    /**
     * Log communication for ticket.
     *
     * @agent-use: Service entry for POST /api/support-tickets/{id}/communications
     * @agent-pattern: Validate -> repo create -> event
     */
    public function create(int $ticketId, array $input): array
    {
        $ticket = $this->tickets->findById($ticketId);
        if (! $ticket) {
            throw new RuntimeException('Ticket not found');
        }
        $data = $this->validator->validate($input + ['ticket_id' => $ticketId]);
        $comm = $this->communications->create($data);
        $this->events->create([
            'ticket_id' => $ticketId,
            'event_type' => 'communication',
            'description' => 'Communication logged',
        ]);
        return ['success' => true, 'data' => $comm];
    }

    /**
     * List communications for ticket.
     *
     * @agent-use: Service entry for GET /api/support-tickets/{id}
     */
    public function listByTicket(int $ticketId): array
    {
        return $this->communications->findByTicket($ticketId);
    }
}
