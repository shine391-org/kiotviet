<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Company permission + sharing API
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class CompanyPermissionApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use AuthTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function share_grants_cross_company_access_and_logs_audit()
    {
        $companyA = $this->createCompany('Company A', 'COMP-A');
        $companyB = $this->createCompany('Company B', 'COMP-B');
        $this->assignPermission($companyA['id'], ['user_id' => 1, 'permissions' => ['read']]);
        $this->assignPermission($companyB['id'], ['user_id' => 99, 'permissions' => ['share', 'read']]);

        $forbidden = $this->withHeaders($this->jsonHeaders())
            ->get("/api/documents/{$companyB['id']}/order/501?user_id=1");
        $this->assertSame(403, $forbidden->response()->getStatusCode());

        $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'company_id' => $companyB['id'],
                'entity_type' => 'order',
                'entity_id' => 501,
                'shared_with_user_id' => 1,
                'permissions' => ['read', 'write'],
                'actor_id' => 99,
            ]))
            ->post('/api/shares')
            ->assertStatus(201);

        $allowed = $this->withHeaders($this->jsonHeaders())
            ->get("/api/documents/{$companyB['id']}/order/501?user_id=1");
        $this->assertSame(200, $allowed->response()->getStatusCode());

        $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'user_id' => 1,
                'changes' => ['status' => 'approved'],
            ]))
            ->put("/api/documents/{$companyB['id']}/order/501")
            ->assertStatus(200);

        $audit = $this->withHeaders($this->jsonHeaders())
            ->get("/api/audit-logs?company_id={$companyB['id']}&entity_type=order&entity_id=501&user_id=1");

        $audit->assertStatus(200);
        $payload = json_decode($audit->getJSON(), true);
        $this->assertNotEmpty($payload['data']);
        $this->assertEquals('approved', $payload['data'][0]['changes']['status']);
    }

    private function jsonHeaders(): array
    {
        return $this->authHeaders(['Content-Type' => 'application/json']);
    }

    private function createCompany(string $name, string $code): array
    {
        $response = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['name' => $name, 'code' => $code]))
            ->post('/api/companies');

        $response->assertStatus(201);
        $parsed = json_decode($response->getJSON(), true);
        return $parsed['data'];
    }

    private function assignPermission(int $companyId, array $payload): void
    {
        $response = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode($payload))
            ->post("/api/companies/{$companyId}/permissions");

        $response->assertStatus(200);
    }
}
