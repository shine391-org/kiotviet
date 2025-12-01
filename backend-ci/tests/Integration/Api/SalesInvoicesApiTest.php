<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Sales invoice API integration
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class SalesInvoicesApiTest extends CIUnitTestCase
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
    public function it_creates_and_submits_invoice_via_api()
    {
        $payload = [
            'customer_id' => 1,
            'posting_date' => '2025-02-01',
            'due_date' => '2025-02-15',
            'items' => [
                ['product_id' => 1, 'description' => 'API Item', 'quantity' => 1, 'rate' => 200],
            ],
            'taxes' => [['tax_name' => 'VAT', 'rate_percent' => 10]],
            'debit_account_id' => $this->debitAccountId,
            'credit_account_id' => $this->creditAccountId,
        ];

        $createRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode($payload))
            ->post('/api/sales-invoices');
        $createRes->assertStatus(201);
        $invoice = $this->getJsonFromResponse($createRes)['data'];

        $submitRes = $this->withHeaders($this->jsonHeaders())
            ->post('/api/sales-invoices/' . $invoice['id'] . '/submit');
        $submitRes->assertStatus(200);

        $entries = $this->db->table('gl_entries')->where('reference_type', 'sales_invoice')->get()->getResultArray();
        $this->assertCount(2, $entries);
    }

    /** @test */
    public function it_rejects_unbalanced_gl()
    {
        $payload = [
            'customer_id' => 1,
            'posting_date' => '2025-02-02',
            'items' => [
                ['product_id' => 1, 'description' => 'API Item', 'quantity' => 1, 'rate' => 200],
            ],
            'taxes' => [['tax_name' => 'VAT', 'rate_percent' => -1]], // invalid
            'debit_account_id' => $this->debitAccountId,
            'credit_account_id' => $this->creditAccountId,
        ];

        $res = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode($payload))
            ->post('/api/sales-invoices');
        $res->assertStatus(400);
    }

    private function seedAccounts(): void
    {
        $assetRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'code' => '1000',
                'name' => 'Assets',
                'account_type' => 'asset',
                'is_group' => true,
            ]))
            ->post('/api/chart-of-accounts');
        $assetRes->assertStatus(201);
        $assetId = $this->getJsonFromResponse($assetRes)['data']['id'];

        $incomeRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'code' => '4000',
                'name' => 'Income',
                'account_type' => 'income',
                'is_group' => true,
            ]))
            ->post('/api/chart-of-accounts');
        $incomeRes->assertStatus(201);
        $incomeId = $this->getJsonFromResponse($incomeRes)['data']['id'];

        $assetLeaf = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'code' => '1100',
                'name' => 'Receivable',
                'account_type' => 'asset',
                'parent_id' => $assetId,
            ]))
            ->post('/api/chart-of-accounts');
        $assetLeaf->assertStatus(201);
        $this->debitAccountId = $this->getJsonFromResponse($assetLeaf)['data']['id'];

        $incomeLeaf = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'code' => '4100',
                'name' => 'Sales',
                'account_type' => 'income',
                'parent_id' => $incomeId,
            ]))
            ->post('/api/chart-of-accounts');
        $incomeLeaf->assertStatus(201);
        $this->creditAccountId = $this->getJsonFromResponse($incomeLeaf)['data']['id'];
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
