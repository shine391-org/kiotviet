<?php

namespace App\Repositories\POS;

use App\Models\POSPaymentMethodModel;
use App\Models\POSProfileModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: POS profiles
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class POSProfileRepository
{
    protected POSProfileModel $profiles;
    protected POSPaymentMethodModel $paymentMethods;
    protected BaseConnection $db;

    public function __construct(
        ?POSProfileModel $profiles = null,
        ?POSPaymentMethodModel $paymentMethods = null,
        ?BaseConnection $db = null
    ) {
        $this->profiles = $profiles ?? new POSProfileModel();
        $this->paymentMethods = $paymentMethods ?? new POSPaymentMethodModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function create(array $data, array $methods = []): array
    {
        $payload = $this->encode($data) + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->profiles->insert($payload);
        $id = (int) $this->profiles->getInsertID();
        $payload['id'] = $id;

        $this->syncMethods($id, $methods);
        $payload['payment_methods'] = $this->getMethods($id);

        return $this->hydrate($payload);
    }

    public function update(int $id, array $data, ?array $methods = null): array
    {
        $payload = $this->encode($data) + ['updated_at' => $this->now()];
        $this->profiles->update($id, $payload);
        if (is_array($methods)) {
            $this->syncMethods($id, $methods);
        }

        $profile = $this->findById($id);
        return $profile ?? ($this->hydrate($payload + ['id' => $id]));
    }

    public function findById(int $id): ?array
    {
        $row = $this->profiles->find($id);
        if (! $row) {
            return null;
        }
        $profile = $this->hydrate($row);
        $profile['payment_methods'] = $this->getMethods($id);
        return $profile;
    }

    public function findForUser(int $userId, ?int $branchId = null): ?array
    {
        $builder = $this->profiles->builder()->where('status', 'active');
        $builder->groupStart()
            ->where('user_id', $userId)
            ->orWhere('user_id IS NULL', null, false)
            ->groupEnd();
        if ($branchId) {
            $builder->groupStart()
                ->where('branch_id', $branchId)
                ->orWhere('branch_id IS NULL', null, false)
                ->groupEnd();
        }
        $row = $builder->orderBy('user_id', 'DESC')->orderBy('id', 'ASC')->get()->getRowArray();
        if (! $row) {
            return null;
        }
        $profile = $this->hydrate($row);
        $profile['payment_methods'] = $this->getMethods((int) $row['id']);
        return $profile;
    }

    public function getMethods(int $profileId): array
    {
        $rows = $this->paymentMethods->builder()
            ->where('profile_id', $profileId)
            ->get()->getResultArray();

        return array_values(array_map(function ($row) {
            $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
            $row['profile_id'] = isset($row['profile_id']) ? (int) $row['profile_id'] : null;
            $row['is_allowed'] = isset($row['is_allowed']) ? (bool) $row['is_allowed'] : false;
            return $row;
        }, $rows));
    }

    public function syncMethods(int $profileId, array $methods): void
    {
        $existing = $this->paymentMethods->builder()->where('profile_id', $profileId)->get()->getResultArray();
        $existingMap = [];
        foreach ($existing as $row) {
            $existingMap[strtoupper((string) $row['payment_method'])] = $row;
        }

        $now = $this->now();
        foreach ($methods as $method) {
            $code = strtoupper(is_array($method) ? ($method['payment_method'] ?? '') : (string) $method);
            if ($code === '') {
                continue;
            }
            $isAllowed = is_array($method) ? (bool) ($method['is_allowed'] ?? true) : true;
            if (isset($existingMap[$code])) {
                $this->paymentMethods->update($existingMap[$code]['id'], [
                    'is_allowed' => $isAllowed ? 1 : 0,
                    'updated_at' => $now,
                ]);
                unset($existingMap[$code]);
                continue;
            }
            $this->paymentMethods->insert([
                'profile_id' => $profileId,
                'payment_method' => $code,
                'is_allowed' => $isAllowed ? 1 : 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! empty($existingMap)) {
            $ids = array_column($existingMap, 'id');
            $this->paymentMethods->builder()->whereIn('id', $ids)->delete();
        }
    }

    private function encode(array $data): array
    {
        if (isset($data['allow_offline'])) {
            $data['allow_offline'] = $data['allow_offline'] ? 1 : 0;
        }
        if (isset($data['require_shift'])) {
            $data['require_shift'] = $data['require_shift'] ? 1 : 0;
        }
        return $data;
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['user_id'] = isset($row['user_id']) ? (int) $row['user_id'] : null;
        $row['role_id'] = isset($row['role_id']) ? (int) $row['role_id'] : null;
        $row['price_list_id'] = isset($row['price_list_id']) ? (int) $row['price_list_id'] : null;
        $row['tax_template_id'] = isset($row['tax_template_id']) ? (int) $row['tax_template_id'] : null;
        $row['warehouse_id'] = isset($row['warehouse_id']) ? (int) $row['warehouse_id'] : null;
        $row['branch_id'] = isset($row['branch_id']) ? (int) $row['branch_id'] : null;
        $row['allow_offline'] = isset($row['allow_offline']) ? (bool) $row['allow_offline'] : false;
        $row['require_shift'] = isset($row['require_shift']) ? (bool) $row['require_shift'] : false;
        $row['credit_limit'] = isset($row['credit_limit']) ? (float) $row['credit_limit'] : 0.0;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
