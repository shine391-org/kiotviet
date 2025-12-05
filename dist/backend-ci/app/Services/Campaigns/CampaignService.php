<?php

namespace App\Services\Campaigns;

use App\Repositories\Campaigns\CampaignRepository;
use App\Validators\CampaignValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * @agent-service: Campaign
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class CampaignService
{
    protected CampaignRepository $repo;
    protected CampaignValidator $validator;

    public function __construct(?CampaignRepository $repo = null, ?CampaignValidator $validator = null)
    {
        $this->repo = $repo ?? new CampaignRepository();
        $this->validator = $validator ?? new CampaignValidator();
    }

    public function create(array $input): array
    {
        $data = $this->validator->validate($input);
        $campaign = $this->repo->create($data);
        return ['success' => true, 'data' => $campaign];
    }

    public function addMember(int $campaignId, array $member): array
    {
        $campaign = $this->repo->findById($campaignId);
        if (! $campaign) {
            throw new RuntimeException('Campaign not found');
        }
        if (empty($member['lead_id']) && empty($member['customer_id'])) {
            throw new InvalidArgumentException('lead_id or customer_id is required');
        }
        $this->repo->addMember($campaignId, $member);
        return ['success' => true];
    }
}
