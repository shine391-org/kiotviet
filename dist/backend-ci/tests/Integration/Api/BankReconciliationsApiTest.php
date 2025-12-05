<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Bank reconciliation API integration
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class BankReconciliationsApiTest extends CIUnitTestCase
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
    public function it_imports_statements_and_matches_payment()
    {
        $payment = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'party_type' => 'customer',
                'party_id' => 1,
                'payment_method' => 'BANK',
                'amount' => 250,
                'debit_account_id' => $this->debitAccountId,
                'credit_account_id' => $this->creditAccountId,
                'reference_no' => 'REF-BANK-1',
                'reference_date' => '2025-08-01',
            ]))
            ->post('/api/payment-entries');
        $payment->assertStatus(201);
        $entry = $this->getJsonFromResponse($payment)['data'];

        $this->withHeaders($this->jsonHeaders())
            ->post('/api/payment-entries/' . $entry['id'] . '/submit')
            ->assertStatus(200);

        $import = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'lines' => [[
                    'account_number' => '001',
                    'amount' => 250,
                    'currency' => 'VND',
                    'reference_no' => 'REF-BANK-1',
                    'reference_date' => '2025-08-01',
                    'description' => 'Bank incoming',
                ]],
            ]))
            ->post('/api/bank-statements/import');
        $import->assertStatus(201);

        $reco = $this->db->table('bank_reconciliations')->get()->getRowArray();
        $this->assertEquals('reconciled', $reco['status']);
    }

    private function seedAccounts(): void
    {
        $asset = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'code' => '9400',
                'name' => 'Bank',
                'account_type' => 'asset',
                'is_group' => true,
            ]))
            ->post('/api/chart-of-accounts');
        $asset->assertStatus(201);
        $assetId = $this->getJsonFromResponse($asset)['data']['id'];

        $liab = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'code' => '9500',
                'name' => 'Clearing',
                'account_type' => 'liability',
                'is_group' => true,
            ]))
            ->post('/api/chart-of-accounts');
        $liab->assertStatus(201);
        $liabId = $this->getJsonFromResponse($liab)['data']['id'];

        $debit = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'code' => '9401',
                'name' => 'Main Bank',
                'account_type' => 'asset',
                'parent_id' => $assetId,
            ]))
            ->post('/api/chart-of-accounts');
        $debit->assertStatus(201);
        $this->debitAccountId = $this->getJsonFromResponse($debit)['data']['id'];

        $credit = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'code' => '9501',
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
