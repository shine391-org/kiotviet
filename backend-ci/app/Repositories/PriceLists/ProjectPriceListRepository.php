<?php

namespace App\Repositories\PriceLists;

use App\Models\ProjectPriceListModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Project price list mapping repository.
 *
 * @agent-repository: Project price lists
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class ProjectPriceListRepository
{
    protected ProjectPriceListModel $model;
    protected BaseConnection $db;

    public function __construct(?ProjectPriceListModel $model = null, ?BaseConnection $db = null)
    {
        $this->model = $model ?? new ProjectPriceListModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    public function activeForProject(int $projectId, string $date): ?array
    {
        $row = $this->db->table('project_price_lists')
            ->where('project_id', $projectId)
            ->where('is_active', 1)
            ->groupStart()->where('valid_from IS NULL', null, false)->orWhere('valid_from <=', $date)->groupEnd()
            ->groupStart()->where('valid_to IS NULL', null, false)->orWhere('valid_to >=', $date)->groupEnd()
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();
        return $row ?: null;
    }
}
