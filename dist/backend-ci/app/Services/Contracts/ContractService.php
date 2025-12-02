<?php

namespace App\Services\Contracts;

use App\Repositories\Contracts\ContractRepository;
use App\Repositories\Contracts\ContractTemplateRepository;
use App\Validators\ContractValidator;
use RuntimeException;

/**
 * @agent-service: Contract lifecycle
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class ContractService
{
    protected ContractRepository $repo;
    protected ContractTemplateRepository $templates;
    protected ContractValidator $validator;

    public function __construct(
        ?ContractRepository $repo = null,
        ?ContractTemplateRepository $templates = null,
        ?ContractValidator $validator = null
    ) {
        $this->repo = $repo ?? new ContractRepository();
        $this->templates = $templates ?? new ContractTemplateRepository();
        $this->validator = $validator ?? new ContractValidator();
    }

    /**
     * Create contract with terms.
     *
     * @agent-use: Service entry for POST /api/contracts
     * @agent-pattern: Validate -> repository create
     */
    public function create(array $input): array
    {
        $data = $this->validator->validate($input);
        if ($data['template_id'] && ! $this->templates->findById((int) $data['template_id'])) {
            throw new RuntimeException('Contract template not found');
        }
        $terms = $data['terms'];
        unset($data['terms']);
        $contract = $this->repo->create($data, $terms);
        return ['success' => true, 'data' => $contract];
    }

    /**
     * Get contract details.
     *
     * @agent-use: Service entry for GET /api/contracts/{id}
     * @agent-pattern: Repository fetch
     */
    public function get(int $id): array
    {
        $contract = $this->repo->findById($id);
        if (! $contract) {
            throw new RuntimeException('Contract not found');
        }
        return ['success' => true, 'data' => $contract];
    }

    /**
     * Activate contract.
     *
     * @agent-use: Service entry for POST /api/contracts/{id}/activate
     * @agent-pattern: Status transition
     */
    public function activate(int $id): array
    {
        $contract = $this->repo->findById($id);
        if (! $contract) {
            throw new RuntimeException('Contract not found');
        }
        $this->repo->updateStatus($id, 'active');
        return ['success' => true, 'data' => $this->repo->findById($id)];
    }

    /**
     * Close contract.
     *
     * @agent-use: Service entry for POST /api/contracts/{id}/close
     * @agent-pattern: Status transition
     */
    public function close(int $id): array
    {
        $contract = $this->repo->findById($id);
        if (! $contract) {
            throw new RuntimeException('Contract not found');
        }
        $this->repo->updateStatus($id, 'closed');
        return ['success' => true, 'data' => $this->repo->findById($id)];
    }

    /**
     * Create contract template.
     *
     * @agent-use: Service entry for POST /api/contract-templates
     * @agent-pattern: Validate -> repository create
     */
    public function createTemplate(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('name is required');
        }
        $tpl = $this->templates->create([
            'name' => $name,
            'terms' => $input['terms'] ?? null,
            'status' => $input['status'] ?? 'active',
        ]);
        return ['success' => true, 'data' => $tpl];
    }

    /**
     * Get template details.
     *
     * @agent-use: Service entry for GET /api/contract-templates/{id}
     * @agent-pattern: Repository fetch
     */
    public function getTemplate(int $id): array
    {
        $tpl = $this->templates->findById($id);
        if (! $tpl) {
            throw new RuntimeException('Contract template not found');
        }
        return ['success' => true, 'data' => $tpl];
    }

    /**
     * Renewal stub.
     *
     * @agent-use: Placeholder for POST /api/contracts/{id}/renew
     * @agent-pattern: Stub response until pricing/invoice integration
     */
    public function renew(int $id): array
    {
        $contract = $this->repo->findById($id);
        if (! $contract) {
            throw new RuntimeException('Contract not found');
        }
        return ['success' => true, 'message' => 'Renewal workflow pending', 'data' => $contract];
    }
}
