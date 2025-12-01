<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Portal + Notification/Assignment API
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class PortalNotificationApiTest extends CIUnitTestCase
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
        $this->seedBase();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_allows_portal_login_and_kb_view_and_triggers_assignment_notification()
    {
        $userRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['email' => 'portal@example.com', 'password' => 'secret', 'customer_id' => 1]))
            ->post('/api/portal/users');
        $userRes->assertStatus(201);

        $loginRes = $this->withBody(json_encode(['email' => 'portal@example.com', 'password' => 'secret']))
            ->post('/api/portal/login');
        $loginRes->assertStatus(200);
        $token = $this->getJsonFromResponse($loginRes)['data']['token'];

        $kbRes = $this->get('/api/knowledge-base/articles');
        $kbRes->assertStatus(200);

        $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'name' => 'Ticket notify',
                'event_type' => 'ticket.created',
                'template' => 'Ticket created',
            ]))
            ->post('/api/notification-rules')
            ->assertStatus(201);

        $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'name' => 'Ticket assignment',
                'entity_type' => 'ticket',
                'team_members' => [1, 2],
            ]))
            ->post('/api/assignment-rules')
            ->assertStatus(201);

        $assignRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['entity_type' => 'ticket', 'entity_id' => 99]))
            ->post('/api/assignment-rules/assign');
        $assignRes->assertStatus(200);

        $notifyRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['event_type' => 'ticket.created', 'entity_type' => 'ticket', 'entity_id' => 99, 'payload' => ['id' => 99]]))
            ->post('/api/notifications/trigger');
        $notifyRes->assertStatus(200);
    }

    private function seedBase(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('knowledge_base_categories')->insert(['id' => 1, 'name' => 'General', 'created_at' => $now]);
        $this->db->table('knowledge_base_articles')->insert([
            'category_id' => 1,
            'title' => 'Welcome',
            'content' => 'Hello',
            'is_published' => 1,
            'created_at' => $now,
        ]);
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
