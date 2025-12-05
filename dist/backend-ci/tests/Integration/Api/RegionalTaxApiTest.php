<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Regional tax API
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class RegionalTaxApiTest extends CIUnitTestCase
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
    public function it_sets_rule_and_previews_tax()
    {
        $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['country' => 'VN', 'rule_json' => ['gst_rate' => 10]]))
            ->post('/api/taxes/regional/rules')
            ->assertStatus(201);

        $preview = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'country' => 'VN',
                'lines' => [['amount' => 100, 'tax_rate' => 0]],
            ]))
            ->post('/api/taxes/regional/preview');
        $preview->assertStatus(200);
    }

    /** @test */
    public function it_creates_withholding_certificate()
    {
        $res = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'country' => 'VN',
                'party_type' => 'supplier',
                'party_id' => 1,
                'base_amount' => 500,
                'withheld_rate' => 5,
            ]))
            ->post('/api/taxes/withholding-certificates');
        $res->assertStatus(201);
    }

    private function jsonHeaders(): array
    {
        return $this->authHeaders(['Content-Type' => 'application/json']);
    }
}
