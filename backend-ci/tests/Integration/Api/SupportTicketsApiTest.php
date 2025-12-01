<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Support tickets + communications API
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class SupportTicketsApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use AuthTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $config = config('Database');
        $config->tests['database'] = 'lanocrm_shop';
        require_once APPPATH . 'Database/Migrations/2025-11-21-000000_TestSchemaSetup.php';
        (new \App\Database\Migrations\TestSchemaSetup())->up();

        $this->setUpDatabase();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_ticket_updates_status_assignment_and_logs_communication()
    {
        $createRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['subject' => 'API ticket', 'priority' => 'low']))
            ->post('/api/support-tickets');
        $createRes->assertStatus(201);
        $ticket = $this->getJsonFromResponse($createRes)['data'];

        $assignRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['assigned_to' => 7]))
            ->post('/api/support-tickets/' . $ticket['id'] . '/assign');
        $assignRes->assertStatus(200);
        $this->assertEquals(7, $this->getJsonFromResponse($assignRes)['data']['assigned_to']);

        $statusRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['status' => 'working']))
            ->post('/api/support-tickets/' . $ticket['id'] . '/status');
        $statusRes->assertStatus(200);
        $this->assertEquals('working', $this->getJsonFromResponse($statusRes)['data']['status']);

        $commRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['type' => 'note', 'content' => 'Followed up via phone']))
            ->post('/api/support-tickets/' . $ticket['id'] . '/communications');
        $commRes->assertStatus(201);

        $showRes = $this->withHeaders($this->jsonHeaders())
            ->get('/api/support-tickets/' . $ticket['id']);
        $showRes->assertStatus(200);
        $this->assertEquals('working', $this->getJsonFromResponse($showRes)['data']['status']);
    }

    private function getJsonFromResponse($response): array
    {
        $raw = $response->getBody();
        $jsonString = $raw;
        if (strpos($raw, '<!DOCTYPE html') !== false && preg_match('/<p>(.*?)<\\/p>/s', $raw, $m)) {
            $jsonString = html_entity_decode($m[1]);
        }
        return json_decode($jsonString, true);
    }

    private function jsonHeaders(): array
    {
        return $this->authHeaders(['Content-Type' => 'application/json']);
    }
}
