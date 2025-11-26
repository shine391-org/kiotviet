<?php

namespace App\Repositories\PriceLists;

use App\Models\PriceListModel;
use CodeIgniter\Database\BaseConnection;

/** Price list persistence. @agent-repository: Price lists @agent-pattern: Repository pattern @agent-reusable: HIGH */
class PriceListRepository
{
    protected PriceListModel $lists;
    protected BaseConnection $db;

    public function __construct(?PriceListModel $lists = null, ?BaseConnection $db = null)
    {
        $this->lists = $lists ?? new PriceListModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    /** List price lists with filters + pagination. */
    public function findAll(array $filters): array
    {
        $b = $this->applyFilters($filters);
        $limit = $filters['limit'] ?? 20;
        $offset = (($filters['page'] ?? 1) - 1) * $limit;
        $rows = $b->orderBy('priority', 'DESC')->orderBy('start_date', 'ASC')->orderBy('id', 'DESC')
            ->limit($limit, $offset)->get()->getResultArray();
        return array_map(fn ($r) => $this->hydrate($r), $rows);
    }

    /** Count price lists with filters. */
    public function count(array $filters): int
    {
        return $this->applyFilters($filters)->countAllResults();
    }

    /** Fetch single price list. */
    public function findById(int $id): ?array
    {
        $row = $this->lists->where('deleted_at', null)->find($id);
        return $row ? $this->hydrate($row) : null;
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $b = $this->lists->where('name', $name)->where('deleted_at', null);
        if ($excludeId) { $b->where('id !=', $excludeId); }
        return $b->countAllResults() > 0;
    }

    /** Create price list row. */
    public function create(array $data): array
    {
        $payload = $this->encode($data) + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->lists->insert($payload);
        $payload['id'] = $this->lists->getInsertID();
        return $this->hydrate($payload);
    }

    /** Update price list row. */
    public function update(int $id, array $data): bool
    {
        $payload = $this->encode($data) + ['updated_at' => $this->now()];
        return (bool) $this->lists->update($id, $payload);
    }

    /** Soft delete. */
    public function delete(int $id): bool
    {
        return (bool) $this->lists->delete($id);
    }

    /** Active price lists applicable to group & date sorted by priority. */
    public function applicablePriceLists(?int $groupId, string $date): array
    {
        $b = $this->lists->builder()
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->groupStart()
                ->where('start_date', null)
                ->orWhere('start_date <=', $date)
            ->groupEnd()
            ->groupStart()
                ->where('end_date', null)
                ->orWhere('end_date >=', $date)
            ->groupEnd()
            ->orderBy('priority', 'DESC')
            ->orderBy('start_date', 'ASC');

        $result = $b->get();
        if ($result === false) {
            $err = $this->db->error();
            throw new \RuntimeException('PriceListRepository query failed: ' . json_encode($err));
        }
        $rows = $result->getResultArray();
        $rows = array_map(fn ($r) => $this->hydrate($r), $rows);

        if ($groupId === null) { return $rows; }

        return array_values(array_filter($rows, static function ($row) use ($groupId) {
            $groups = $row['apply_to_groups'] ?? [];
            return empty($groups) || in_array($groupId, $groups, true);
        }));
    }

    private function applyFilters(array $filters)
    {
        $b = $this->lists->builder()->where('deleted_at', null);
        if (! empty($filters['search'])) { $b->like('name', $filters['search']); }
        if (! empty($filters['type'])) { $b->where('type', $filters['type']); }
        if (! empty($filters['apply_to_group_id'])) {
            $gid = (int) $filters['apply_to_group_id'];
            // apply_to_groups is JSON array; match null (apply to all) or contains gid
            $b->groupStart()
                ->where('apply_to_groups', null)
                ->orWhere('JSON_CONTAINS(apply_to_groups, ?)', [json_encode($gid)])
                ->groupEnd();
        }
        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $b->where('is_active', $filters['is_active'] ? 1 : 0);
        }

        if (! empty($filters['status'])) {
            $today = date('Y-m-d');
            switch ($filters['status']) {
                case 'active':
                    $b->where('is_active', 1)
                        ->groupStart()->where('start_date', null)->orWhere('start_date <=', $today)->groupEnd()
                        ->groupStart()->where('end_date', null)->orWhere('end_date >=', $today)->groupEnd();
                    break;
                case 'upcoming':
                    $b->where('is_active', 1)->where('start_date >', $today);
                    break;
                case 'expired':
                    $b->where('is_active', 1)->where('end_date <', $today);
                    break;
                case 'inactive':
                    $b->where('is_active', 0);
                    break;
            }
        }
        return $b;
    }

    public function dependentLists(int $priceListId): array
    {
        return $this->lists->where('deleted_at', null)
            ->where('base_price_list_id', $priceListId)
            ->findAll();
    }

    private function hydrate(array $row): array
    {
        if (isset($row['apply_to_groups'])) {
            $decoded = is_array($row['apply_to_groups'])
                ? $row['apply_to_groups']
                : (json_decode((string) $row['apply_to_groups'], true) ?: []);
            if (! is_array($decoded)) { $decoded = []; }
            $row['apply_to_groups'] = array_values(array_map('intval', $decoded));
        }
        $row['auto_update'] = isset($row['auto_update']) ? (bool) $row['auto_update'] : false;
        return $row;
    }

    private function encode(array $data): array
    {
        if (isset($data['apply_to_groups'])) {
            $groups = array_values(array_filter(array_map('intval', (array) $data['apply_to_groups'])));
            $data['apply_to_groups'] = $groups ? json_encode($groups) : null;
        }
        if (array_key_exists('auto_update', $data)) {
            $data['auto_update'] = $data['auto_update'] ? 1 : 0;
        }
        return $data;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
