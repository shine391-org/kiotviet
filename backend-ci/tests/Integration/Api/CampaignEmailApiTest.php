<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Campaign + email campaign API integration
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class CampaignEmailApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use AuthTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();

        $this->setUpDatabase();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_campaign_adds_member_and_schedules_email_campaign()
    {
        $campaignRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['name' => 'Launch', 'budget' => 500]))
            ->post('/api/campaigns');
        $campaignRes->assertStatus(201);
        $campaign = $this->getJsonFromResponse($campaignRes)['data'];

        $memberRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['lead_id' => 1]))
            ->post('/api/campaigns/' . $campaign['id'] . '/members');
        $memberRes->assertStatus(200);

        $emailRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'campaign_id' => $campaign['id'],
                'subject' => 'Hello',
                'template' => 'Body',
                'status' => 'scheduled',
            ]))
            ->post('/api/email-campaigns');
        $emailRes->assertStatus(201);
        $email = $this->getJsonFromResponse($emailRes)['data'];
        $this->assertEquals('scheduled', $email['status']);
    }

    private function getJsonFromResponse($response): array
    {
        $raw = $response->getBody();
        $jsonString = $raw;
        if (strpos($raw, '<!DOCTYPE html') !== false && preg_match('/<p>(.*?)<\/p>/s', $raw, $m)) {
            $jsonString = html_entity_decode($m[1]);
        }
        return json_decode($jsonString, true);
    }

    private function jsonHeaders(): array
    {
        return $this->authHeaders(['Content-Type' => 'application/json']);
    }
}
