<?php

namespace App\Services\PriceLists;

use App\Repositories\PriceLists\PriceListItemRepository;
use App\Repositories\PriceLists\PriceListRepository;
use App\Validators\PriceListValidator;
use InvalidArgumentException;
use RuntimeException;

/** Price list business logic. @agent-service: Price lists @agent-pattern: Service orchestrator @agent-reusable: HIGH */
class PriceListService
{
    protected PriceListRepository $repo;
    protected PriceListItemRepository $items;
    protected PriceListValidator $validator;

    public function __construct(
        ?PriceListRepository $repo = null,
        ?PriceListItemRepository $items = null,
        ?PriceListValidator $validator = null
    ) {
        $this->repo = $repo ?? new PriceListRepository();
        $this->items = $items ?? new PriceListItemRepository();
        $this->validator = $validator ?? new PriceListValidator();
    }

    /** List price lists with computed status. */
    public function list(array $filters): array
    {
        $validated = $this->validator->validateListFilters($filters);
        $rows = $this->repo->findAll($validated);
        $total = $this->repo->count($validated);
        foreach ($rows as &$row) { $row['status'] = $this->status($row); }
        if ($validated['apply_to_group_id'] ?? null) {
            $gid = (int) $validated['apply_to_group_id'];
            $rows = array_values(array_filter($rows, static function ($row) use ($gid) {
                $groups = $row['apply_to_groups'] ?? [];
                return empty($groups) || in_array($gid, $groups, true);
            }));
            $total = count($rows);
        }
        return [
            'success' => true,
            'data' => $rows,
            'pagination' => $this->formatPagination($validated, $total),
        ];
    }

    /** Get a single price list. */
    public function get(int $id): array
    {
        $row = $this->requirePriceList($id);
        $row['status'] = $this->status($row);
        return ['success' => true, 'data' => $row];
    }

    /** Create price list. */
    public function create(array $data): array
    {
        $validated = $this->validator->validateCreate($data);
        if ($this->repo->nameExists($validated['name'])) {
            throw new InvalidArgumentException('Price list name already exists');
        }
        $row = $this->repo->create($validated);
        $row['status'] = $this->status($row);
        return ['success' => true, 'data' => $row];
    }

    /** Update price list. */
    public function update(int $id, array $data): array
    {
        $this->requirePriceList($id);
        $validated = $this->validator->validateUpdate($data);
        if (! empty($validated['name']) && $this->repo->nameExists($validated['name'], $id)) {
            throw new InvalidArgumentException('Price list name already exists');
        }
        $this->repo->update($id, $validated);
        return ['success' => true];
    }

    /** Delete price list (soft). */
    public function delete(int $id): array
    {
        $this->requirePriceList($id);
        return ['success' => $this->repo->delete($id)];
    }

    /** List items for a price list. */
    public function items(int $priceListId): array
    {
        $this->requirePriceList($priceListId);
        return ['success' => true, 'data' => $this->items->itemsByPriceList($priceListId)];
    }

    /** Replace items in bulk. */
    public function upsertItems(int $priceListId, array $items): array
    {
        $this->requirePriceList($priceListId);
        $validated = $this->validator->validateItems($items);
        $result = $this->items->replaceItems($priceListId, $validated);
        return ['success' => true, 'inserted' => $result['inserted']];
    }

    /** Expose applicable lists to other services. */
    public function applicable(?int $groupId, string $date): array
    {
        return $this->repo->applicablePriceLists($groupId, $date);
    }

    private function requirePriceList(int $id): array
    {
        $row = $this->repo->findById($id);
        if (! $row) { throw new RuntimeException('Price list not found'); }
        return $row;
    }

    private function status(array $row): string
    {
        if (! ($row['is_active'] ?? true)) { return 'inactive'; }
        $today = date('Y-m-d');
        $start = $row['start_date'] ?? null;
        $end = $row['end_date'] ?? null;
        if ($start && $start > $today) { return 'upcoming'; }
        if ($end && $end < $today) { return 'expired'; }
        return 'active';
    }

    private function formatPagination(array $filters, int $total): array
    {
        $limit = $filters['limit'] ?? 20;
        $page = $filters['page'] ?? 1;
        $totalPages = (int) ceil($total / ($limit ?: 1));
        return ['page' => $page, 'limit' => $limit, 'total' => $total, 'total_pages' => $totalPages];
    }
}
