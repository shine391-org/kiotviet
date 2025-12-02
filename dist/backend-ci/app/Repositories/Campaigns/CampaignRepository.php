<?php

namespace App\Repositories\Campaigns;

use App\Models\CampaignModel;
use App\Models\CampaignMemberModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Campaigns
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class CampaignRepository
{
    protected CampaignModel $campaigns;
    protected CampaignMemberModel $members;
    protected BaseConnection $db;

    public function __construct(?CampaignModel $campaigns = null, ?CampaignMemberModel $members = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->campaigns = $campaigns ?? new CampaignModel();
        $this->members = $members ?? new CampaignMemberModel();
    }

    public function create(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->campaigns->insert($payload);
        $payload['id'] = (int) $this->campaigns->getInsertID();
        return $this->hydrate($payload);
    }

    public function addMember(int $campaignId, array $member): void
    {
        $row = [
            'campaign_id' => $campaignId,
            'lead_id' => $member['lead_id'] ?? null,
            'customer_id' => $member['customer_id'] ?? null,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];
        $this->members->insert($row);
    }

    public function findById(int $id): ?array
    {
        $row = $this->campaigns->find($id);
        if (! $row) { return null; }
        $members = $this->members->where('campaign_id', $id)->get()->getResultArray();
        $campaign = $this->hydrate($row);
        $campaign['members'] = array_map(fn ($m) => $this->hydrateMember($m), $members);
        return $campaign;
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['budget'] = isset($row['budget']) ? (float) $row['budget'] : 0.0;
        return $row;
    }

    private function hydrateMember(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['campaign_id'] = isset($row['campaign_id']) ? (int) $row['campaign_id'] : null;
        $row['lead_id'] = isset($row['lead_id']) ? (int) $row['lead_id'] : null;
        $row['customer_id'] = isset($row['customer_id']) ? (int) $row['customer_id'] : null;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
