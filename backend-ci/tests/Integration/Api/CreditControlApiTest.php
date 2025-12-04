<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Credit control API integration
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class CreditControlApiTest extends CIUnitTestCase
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
    public function it_rejects_over_credit()
    {
        $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['customer_id' => 1, 'limit_amount' => 100, 'on_hold' => false]))
            ->post('/api/credit-limits')
            ->assertStatus(200);

        $res = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['customer_id' => 1, 'amount' => 150]))
            ->post('/api/credit-check');
        $res->assertStatus(400);
    }

    private function jsonHeaders(): array
    {
        return $this->authHeaders(['Content-Type' => 'application/json']);
    }
}
