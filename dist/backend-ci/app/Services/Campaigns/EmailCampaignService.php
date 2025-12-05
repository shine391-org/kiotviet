<?php

namespace App\Services\Campaigns;

use App\Repositories\Campaigns\CampaignRepository;
use App\Repositories\Campaigns\EmailCampaignRepository;
use App\Validators\EmailCampaignValidator;
use RuntimeException;

/**
 * @agent-service: Email campaign
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class EmailCampaignService
{
    protected EmailCampaignRepository $repo;
    protected CampaignRepository $campaigns;
    protected EmailCampaignValidator $validator;

    public function __construct(
        ?EmailCampaignRepository $repo = null,
        ?CampaignRepository $campaigns = null,
        ?EmailCampaignValidator $validator = null
    ) {
        $this->repo = $repo ?? new EmailCampaignRepository();
        $this->campaigns = $campaigns ?? new CampaignRepository();
        $this->validator = $validator ?? new EmailCampaignValidator();
    }

    public function schedule(array $input): array
    {
        $data = $this->validator->validate($input);
        if ($data['campaign_id'] && ! $this->campaigns->findById((int) $data['campaign_id'])) {
            throw new RuntimeException('Campaign not found');
        }
        $emailCampaign = $this->repo->create($data);
        return ['success' => true, 'data' => $emailCampaign];
    }

    public function updateStatus(int $id, string $status): array
    {
        $campaign = $this->repo->findById($id);
        if (! $campaign) {
            throw new RuntimeException('Email campaign not found');
        }
        $this->repo->updateStatus($id, $status);
        return ['success' => true, 'data' => $this->repo->findById($id)];
    }

    public function log(int $id, ?int $memberId, string $status, ?string $message = null): void
    {
        $this->repo->log($id, $memberId, $status, $message);
    }
}
