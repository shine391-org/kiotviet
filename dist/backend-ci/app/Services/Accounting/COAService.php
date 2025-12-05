<?php

namespace App\Services\Accounting;

use App\Repositories\Accounting\COARepository;
use App\Validators\COAValidator;
use RuntimeException;

/**
 * @agent-service: Chart of accounts
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class COAService
{
    protected COARepository $repo;
    protected COAValidator $validator;

    public function __construct(?COARepository $repo = null, ?COAValidator $validator = null)
    {
        $this->repo = $repo ?? new COARepository();
        $this->validator = $validator ?? new COAValidator();
    }

    /**
     * Create account (group or leaf).
     *
     * @agent-use: POST /api/chart-of-accounts
     * @agent-pattern: Validate -> parent check -> repo create
     */
    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        if ($this->repo->codeExists($data['code'])) {
            throw new RuntimeException('Account code already exists');
        }

        if ($data['parent_id']) {
            $parent = $this->repo->findById($data['parent_id']);
            if (! $parent) {
                throw new RuntimeException('Parent account not found');
            }
            if (! $parent['is_group']) {
                throw new RuntimeException('Parent account must be a group');
            }
        }

        $account = $this->repo->create($data);
        return ['success' => true, 'data' => $account];
    }

    /**
     * Get account.
     */
    public function get(int $id): array
    {
        $account = $this->repo->findById($id);
        if (! $account) {
            throw new RuntimeException('Account not found');
        }
        return ['success' => true, 'data' => $account];
    }

    /**
     * List children for hierarchy rendering.
     */
    public function listChildren(?int $parentId = null): array
    {
        return ['success' => true, 'data' => $this->repo->listChildren($parentId)];
    }
}
