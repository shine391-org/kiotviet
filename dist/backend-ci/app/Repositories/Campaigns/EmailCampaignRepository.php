<?php

namespace App\Repositories\Campaigns;

use App\Models\EmailCampaignLogModel;
use App\Models\EmailCampaignModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Email campaigns
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class EmailCampaignRepository
{
    protected EmailCampaignModel $campaigns;
    protected EmailCampaignLogModel $logs;
    protected BaseConnection $db;

    public function __construct(?EmailCampaignModel $campaigns = null, ?EmailCampaignLogModel $logs = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->campaigns = $campaigns ?? new EmailCampaignModel();
        $this->logs = $logs ?? new EmailCampaignLogModel();
    }

    public function create(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->campaigns->insert($payload);
        $payload['id'] = (int) $this->campaigns->getInsertID();
        return $this->hydrate($payload);
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->campaigns->update($id, ['status' => $status, 'updated_at' => $this->now()]);
    }

    public function log(int $emailCampaignId, ?int $memberId, string $status, ?string $message = null): void
    {
        $this->logs->insert([
            'email_campaign_id' => $emailCampaignId,
            'member_id' => $memberId,
            'status' => $status,
            'message' => $message,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);
    }

    public function findById(int $id): ?array
    {
        $row = $this->campaigns->find($id);
        return $row ? $this->hydrate($row) : null;
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['campaign_id'] = isset($row['campaign_id']) ? (int) $row['campaign_id'] : null;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
