<?php

namespace App\Services\BankAccounts;

use App\Repositories\BankAccounts\BankAccountRepository;
use InvalidArgumentException;
use RuntimeException;

/**
 * Bank Account business logic.
 *
 * @agent-service: BankAccounts
 * @agent-pattern: Service orchestrator
 */
class BankAccountService
{
    protected BankAccountRepository $repo;

    public function __construct(?BankAccountRepository $repo = null)
    {
        $this->repo = $repo ?? new BankAccountRepository();
    }

    /** List bank accounts */
    public function list(array $filters): array
    {
        $rows = $this->repo->findAll($filters);
        $total = $this->repo->count($filters);

        return [
            'success' => true,
            'data' => array_map([$this, 'transform'], $rows),
            'pagination' => $this->pagination($filters, $total),
        ];
    }

    /** Get single bank account */
    public function get(int $id): array
    {
        $account = $this->repo->findById($id);
        if (!$account) {
            throw new RuntimeException('Bank account not found');
        }
        return ['success' => true, 'data' => $this->transform($account)];
    }

    /** Create bank account */
    public function create(array $payload): array
    {
        $validated = $this->validate($payload);
        $account = $this->repo->create($validated);

        return [
            'success' => true,
            'data' => $this->transform($account),
            'message' => 'Bank account created successfully',
        ];
    }

    /** Update bank account */
    public function update(int $id, array $payload): array
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new RuntimeException('Bank account not found');
        }

        $validated = $this->validate($payload, false);
        $account = $this->repo->update($id, $validated);

        return [
            'success' => true,
            'data' => $this->transform($account),
            'message' => 'Bank account updated successfully',
        ];
    }

    /** Delete bank account */
    public function delete(int $id): array
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new RuntimeException('Bank account not found');
        }

        $this->repo->delete($id);

        return [
            'success' => true,
            'message' => 'Bank account deleted successfully',
        ];
    }

    /** Generate QR code URL for payment */
    public function generateQR(int $id, float $amount, ?string $description = null): array
    {
        $account = $this->repo->findById($id);
        if (!$account) {
            throw new RuntimeException('Bank account not found');
        }

        // VietQR format
        $qrUrl = $this->buildVietQRUrl($account, $amount, $description);

        return [
            'success' => true,
            'data' => [
                'qr_url' => $qrUrl,
                'bank_name' => $account['bank_name'],
                'account_number' => $account['account_number'],
                'account_name' => $account['account_name'],
                'amount' => $amount,
                'description' => $description,
            ],
        ];
    }

    private function validate(array $data, bool $isCreate = true): array
    {
        $validated = [];

        if ($isCreate) {
            if (empty($data['bank_name'])) {
                throw new InvalidArgumentException('Bank name is required');
            }
            if (empty($data['account_number'])) {
                throw new InvalidArgumentException('Account number is required');
            }
            if (empty($data['account_name'])) {
                throw new InvalidArgumentException('Account name is required');
            }
        }

        $fields = ['bank_name', 'bank_code', 'account_number', 'account_name', 'branch_name', 'branch_id', 'is_default', 'is_active', 'qr_template', 'notes', 'sort_order', 'created_by'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $validated[$field] = $data[$field];
            }
        }

        return $validated;
    }

    private function transform(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'bank_name' => $row['bank_name'],
            'bank_code' => $row['bank_code'] ?? null,
            'account_number' => $row['account_number'],
            'account_name' => $row['account_name'],
            'branch_name' => $row['branch_name'] ?? null,
            'branch_id' => isset($row['branch_id']) ? (int) $row['branch_id'] : null,
            'is_default' => (bool) ($row['is_default'] ?? false),
            'is_active' => (bool) ($row['is_active'] ?? true),
            'qr_template' => $row['qr_template'] ?? null,
            'notes' => $row['notes'] ?? null,
            'display_name' => $row['bank_name'] . ' - ' . $row['account_number'] . ' - ' . $row['account_name'],
        ];
    }

    private function buildVietQRUrl(array $account, float $amount, ?string $description): string
    {
        // Use VietQR API format
        $bankCode = $account['bank_code'] ?? 'BIDV';
        $accountNo = $account['account_number'];
        $accountName = $account['account_name'];
        $desc = $description ?? '';

        // VietQR quick link format
        return sprintf(
            'https://img.vietqr.io/image/%s-%s-compact2.png?amount=%d&addInfo=%s&accountName=%s',
            $bankCode,
            $accountNo,
            (int) $amount,
            urlencode($desc),
            urlencode($accountName)
        );
    }

    private function pagination(array $filters, int $total): array
    {
        $limit = $filters['limit'] ?? 50;
        $page = $filters['page'] ?? 1;
        $totalPages = (int) ceil($total / ($limit ?: 1));
        return [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => $totalPages,
        ];
    }
}
