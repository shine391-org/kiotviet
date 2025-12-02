<?php

namespace App\Services\CRM;

use App\Repositories\CRM\LeadRepository;
use App\Validators\LeadValidator;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;

/**
 * @agent-service: Lead
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class LeadService
{
    protected LeadRepository $repo;
    protected LeadValidator $validator;
    protected BaseConnection $db;

    public function __construct(?LeadRepository $repo = null, ?LeadValidator $validator = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->repo = $repo ?? new LeadRepository(null, $this->db);
        $this->validator = $validator ?? new LeadValidator();
    }

    /**
     * Create lead.
     *
     * @agent-use: POST /api/leads
     */
    public function create(array $input): array
    {
        $data = $this->validator->validate($input);
        $lead = $this->repo->create($data);
        return ['success' => true, 'data' => $lead];
    }

    /**
     * Convert lead to customer (basic fields).
     *
     * @agent-use: POST /api/leads/{id}/convert
     */
    public function convertToCustomer(int $id): array
    {
        $lead = $this->repo->findById($id);
        if (! $lead) {
            throw new RuntimeException('Lead not found');
        }
        $now = date('Y-m-d H:i:s');
        $this->db->table('customers')->insert([
            'name' => $lead['name'],
            'email' => $lead['email'],
            'phone' => $lead['phone'],
            'customer_type' => 'INDIVIDUAL',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $customerId = (int) $this->db->insertID();
        return ['success' => true, 'data' => ['customer_id' => $customerId]];
    }
}
