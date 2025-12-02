<?php

namespace App\Services\Accounting;

use App\Repositories\Accounting\CreditLimitRepository;
use App\Validators\CreditControlValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * @agent-service: Credit control
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class CreditControlService
{
    protected CreditLimitRepository $repo;
    protected CreditControlValidator $validator;

    public function __construct(?CreditLimitRepository $repo = null, ?CreditControlValidator $validator = null)
    {
        $this->repo = $repo ?? new CreditLimitRepository();
        $this->validator = $validator ?? new CreditControlValidator();
    }

    public function upsertLimit(array $input): array
    {
        $data = $this->validator->validateLimit($input);
        $limit = $this->repo->upsert($data['customer_id'], $data['limit_amount'], $data['on_hold']);
        return ['success' => true, 'data' => $limit];
    }

    /**
     * Check credit limit; throw if exceeded or on hold (unless override).
     *
     * @agent-use: Order/Invoice check
     */
    public function assertWithinLimit(int $customerId, float $amount, bool $allowOverride = false): void
    {
        $limit = $this->repo->findByCustomer($customerId);
        if (! $limit) {
            return;
        }
        if ($limit['on_hold'] && ! $allowOverride) {
            throw new RuntimeException('Customer is on hold');
        }
        if ($amount > $limit['limit_amount'] && ! $allowOverride) {
            throw new InvalidArgumentException('Credit limit exceeded');
        }
    }

    public function check(array $input): array
    {
        $data = $this->validator->validateCheck($input);
        $this->assertWithinLimit($data['customer_id'], $data['amount'], $data['allow_override']);
        return ['success' => true];
    }
}
