<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Withholding rules API
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class WithholdingRulesApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use AuthTestTrait;

    private int $ruleId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->setUpAuthToken();
        $this->ruleId = $this->createRule();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_applies_withholding_rule_by_threshold()
    {
        $below = $this->withHeaders($this->jsonHeaders())
            ->get('/api/withholding-rules/' . $this->ruleId . '/apply?amount=50');
        $below->assertStatus(200);
        $belowPayload = $this->decode($below);
        $this->assertEquals(0.0, (float) $belowPayload['amount']);

        $above = $this->withHeaders($this->jsonHeaders())
            ->get('/api/withholding-rules/' . $this->ruleId . '/apply?amount=200');
        $above->assertStatus(200);
        $abovePayload = $this->decode($above);
        $this->assertEquals(20.0, (float) $abovePayload['amount']);
    }

    private function createRule(): int
    {
        $res = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'name' => 'WHT 10%',
                'rate_percent' => 10,
                'apply_threshold' => 100,
            ]))
            ->post('/api/withholding-rules');
        $res->assertStatus(201);
        $payload = $this->decode($res);
        return (int) $payload['data']['id'];
    }

    private function jsonHeaders(): array
    {
        return $this->authHeaders(['Content-Type' => 'application/json']);
    }

    private function decode($response): array
    {
        $raw = $response->getBody();
        if (strpos($raw, '<!DOCTYPE html') !== false && preg_match('/<p>(.*?)<\\/p>/s', $raw, $m)) {
            $raw = html_entity_decode($m[1]);
        }
        return json_decode($raw, true);
    }
}
