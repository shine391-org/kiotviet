<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Payment entry API integration
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class PaymentEntriesApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use AuthTestTrait;

    private int $debitAccountId;
    private int $creditAccountId;

    protected function setUp(): void
    {
        parent::setUp();
        $config = config('Database');
        $config->tests['database'] = 'lanocrm_shop';
        require_once APPPATH . 'Database/Migrations/2025-11-21-000000_TestSchemaSetup.php';
        (new \App\Database\Migrations\TestSchemaSetup())->up();

        $this->setUpDatabase();
        $this->setUpAuthToken();
        $this->seedAccounts();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_and_submits_payment_entry_via_api()
    {
        $payload = [
            'party_type' => 'customer',
            'party_id' => 1,
            'payment_method' => 'BANK',
            'amount' => 150,
            'debit_account_id' => $this->debitAccountId,
            'credit_account_id' => $this->creditAccountId,
            'reference_no' => 'PE-API-1',
            'reference_date' => '2025-07-01',
        ];

        $create = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode($payload))
            ->post('/api/payment-entries');
        $create->assertStatus(201);
        $entry = $this->getJsonFromResponse($create)['data'];

        $submit = $this->withHeaders($this->jsonHeaders())
            ->post('/api/payment-entries/' . $entry['id'] . '/submit');
        $submit->assertStatus(200);

        $gl = $this->db->table('gl_entries')->where('reference_type', 'payment_entry')->get()->getResultArray();
        $this->assertCount(2, $gl);
    }

    private function seedAccounts(): void
    {
        $asset = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'code' => '9200',
                'name' => 'Bank',
                'account_type' => 'asset',
                'is_group' => true,
            ]))
            ->post('/api/chart-of-accounts');
        $asset->assertStatus(201);
        $assetId = $this->getJsonFromResponse($asset)['data']['id'];

        $liab = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'code' => '9300',
                'name' => 'Clearing',
                'account_type' => 'liability',
                'is_group' => true,
            ]))
            ->post('/api/chart-of-accounts');
        $liab->assertStatus(201);
        $liabId = $this->getJsonFromResponse($liab)['data']['id'];

        $debit = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'code' => '9201',
                'name' => 'Main Bank',
                'account_type' => 'asset',
                'parent_id' => $assetId,
            ]))
            ->post('/api/chart-of-accounts');
        $debit->assertStatus(201);
        $this->debitAccountId = $this->getJsonFromResponse($debit)['data']['id'];

        $credit = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'code' => '9301',
                'name' => 'Clearing Acc',
                'account_type' => 'liability',
                'parent_id' => $liabId,
            ]))
            ->post('/api/chart-of-accounts');
        $credit->assertStatus(201);
        $this->creditAccountId = $this->getJsonFromResponse($credit)['data']['id'];
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
