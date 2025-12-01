<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Accounting API integration
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class AccountingApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use AuthTestTrait;

    private int $cashId;
    private int $salesId;

    protected function setUp(): void
    {
        parent::setUp();
        $config = config('Database');
        $config->tests['database'] = 'lanocrm_shop';
        require_once APPPATH . 'Database/Migrations/2025-11-21-000000_TestSchemaSetup.php';
        (new \App\Database\Migrations\TestSchemaSetup())->up();

        $this->setUpDatabase();
        $this->setUpAuthToken();
        $this->seedAccountsViaApi();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_posts_balanced_journal_via_api()
    {
        $payload = [
            'posting_date' => '2025-01-05',
            'entries' => [
                ['account_id' => $this->cashId, 'debit' => 200, 'credit' => 0, 'remarks' => 'Receive cash'],
                ['account_id' => $this->salesId, 'debit' => 0, 'credit' => 200, 'remarks' => 'Revenue'],
            ],
        ];

        $res = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode($payload))
            ->post('/api/gl/journal');
        $res->assertStatus(201);

        $rows = $this->db->table('gl_entries')->get()->getResultArray();
        $this->assertCount(2, $rows);
    }

    /** @test */
    public function it_rejects_unbalanced_journal_via_api()
    {
        $payload = [
            'posting_date' => '2025-01-06',
            'entries' => [
                ['account_id' => $this->cashId, 'debit' => 50],
                ['account_id' => $this->salesId, 'credit' => 30],
            ],
        ];

        $res = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode($payload))
            ->post('/api/gl/journal');
        $res->assertStatus(400);
    }

    private function seedAccountsViaApi(): void
    {
        $asset = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'code' => '1000',
                'name' => 'Assets',
                'account_type' => 'asset',
                'is_group' => true,
            ]))
            ->post('/api/chart-of-accounts');
        $asset->assertStatus(201);
        $assetId = $this->getJsonFromResponse($asset)['data']['id'];

        $income = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'code' => '4000',
                'name' => 'Income',
                'account_type' => 'income',
                'is_group' => true,
            ]))
            ->post('/api/chart-of-accounts');
        $income->assertStatus(201);
        $incomeId = $this->getJsonFromResponse($income)['data']['id'];

        $cash = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'code' => '1100',
                'name' => 'Cash',
                'account_type' => 'asset',
                'parent_id' => $assetId,
            ]))
            ->post('/api/chart-of-accounts');
        $cash->assertStatus(201);
        $this->cashId = $this->getJsonFromResponse($cash)['data']['id'];

        $sales = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'code' => '4100',
                'name' => 'Sales',
                'account_type' => 'income',
                'parent_id' => $incomeId,
            ]))
            ->post('/api/chart-of-accounts');
        $sales->assertStatus(201);
        $this->salesId = $this->getJsonFromResponse($sales)['data']['id'];
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
