<?php

namespace Tests\Services;

use App\Services\Campaigns\CampaignService;
use App\Repositories\Campaigns\CampaignRepository;
use App\Validators\CampaignValidator;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;
use InvalidArgumentException;

class CampaignServiceTest extends CIUnitTestCase
{
    private CampaignService $service;
    private CampaignFakeRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new CampaignFakeRepo();
        $validator = new class extends CampaignValidator {
            public function validate(array $data): array { return $data; }
        };
        $this->service = new CampaignService($this->repo, $validator);
    }

    public function testCreateSuccess(): void
    {
        $data = ['name' => 'Summer Sale', 'type' => 'promotion'];
        $result = $this->service->create($data);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testAddMemberWithLeadId(): void
    {
        $result = $this->service->addMember(1, ['lead_id' => 5]);
        
        $this->assertTrue($result['success']);
    }

    public function testAddMemberWithCustomerId(): void
    {
        $result = $this->service->addMember(1, ['customer_id' => 10]);
        
        $this->assertTrue($result['success']);
    }

    public function testAddMemberThrowsWhenCampaignNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Campaign not found');
        
        $this->service->addMember(999, ['lead_id' => 5]);
    }

    public function testAddMemberThrowsWhenNoLeadOrCustomerId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('lead_id or customer_id is required');
        
        $this->service->addMember(1, []);
    }

    public function testAddMemberThrowsWhenBothEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->service->addMember(1, ['lead_id' => '', 'customer_id' => '']);
    }
}

class CampaignFakeRepo extends CampaignRepository
{
    private array $campaignData = [
        1 => ['id' => 1, 'name' => 'Test Campaign', 'type' => 'promotion'],
    ];
    private array $memberData = [];

    public function __construct() {}

    public function findById(int $id): ?array
    {
        return $this->campaignData[$id] ?? null;
    }

    public function create(array $data): array
    {
        $id = max(array_keys($this->campaignData) ?: [0]) + 1;
        $this->campaignData[$id] = array_merge($data, ['id' => $id]);
        return $this->campaignData[$id];
    }

    public function addMember(int $campaignId, array $member): void
    {
        $this->memberData[] = array_merge($member, ['campaign_id' => $campaignId]);
    }
}
