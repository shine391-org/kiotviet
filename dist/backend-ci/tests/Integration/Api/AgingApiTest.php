<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Aging API integration
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class AgingApiTest extends CIUnitTestCase
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
        $this->seedGL();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_returns_aging_report()
    {
        $res = $this->withHeaders($this->jsonHeaders())
            ->get('/api/aging?party_type=customer&as_of=2025-01-20&base_currency=VND');
        $res->assertStatus(200);
        $data = $this->getJsonFromResponse($res)['data'];
        $this->assertNotEmpty($data);
    }

    private function seedGL(): void
    {
        $this->db->table('exchange_rates')->insert([
            'currency' => 'USD',
            'rate' => 24000,
            'valid_from' => '2025-01-01',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->table('gl_entries')->insertBatch([
            [
                'posting_date' => '2025-01-05',
                'account_id' => 1,
                'party_type' => 'customer',
                'party_id' => 1,
                'debit' => 200,
                'credit' => 0,
                'currency' => 'VND',
            ],
            [
                'posting_date' => '2024-10-01',
                'account_id' => 1,
                'party_type' => 'customer',
                'party_id' => 1,
                'debit' => 0,
                'credit' => 50,
                'currency' => 'USD',
            ],
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
