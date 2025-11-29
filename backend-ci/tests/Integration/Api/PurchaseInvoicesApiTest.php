<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Purchase invoice API integration
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class PurchaseInvoicesApiTest extends CIUnitTestCase
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
        require_once APPPATH . 'Database/Migrations/2025-11-27-000999_TestSchemaSetup.php';
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
    public function it_creates_and_submits_purchase_invoice_via_api()
    {
        $payload = [
            'supplier_id' => 2,
            'posting_date' => '2025-04-01',
            'due_date' => '2025-04-10',
            'items' => [
                ['product_id' => 1, 'description' => 'API Purchase', 'quantity' => 2, 'rate' => 200],
            ],
            'taxes' => [['tax_name' => 'VAT', 'rate_percent' => 10]],
            'debit_account_id' => $this->debitAccountId,
            'credit_account_id' => $this->creditAccountId,
        ];

        $create = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode($payload))
            ->post('/api/purchase-invoices');
        $create->assertStatus(201);
        $invoice = $this->getJsonFromResponse($create)['data'];

        $submit = $this->withHeaders($this->jsonHeaders())
            ->post('/api/purchase-invoices/' . $invoice['id'] . '/submit');
        $submit->assertStatus(200);

        $entries = $this->db->table('gl_entries')->where('reference_type', 'purchase_invoice')->get()->getResultArray();
        $this->assertCount(2, $entries);

        $cancel = $this->withHeaders($this->jsonHeaders())
            ->post('/api/purchase-invoices/' . $invoice['id'] . '/cancel');
        $cancel->assertStatus(200);
    }

    private function seedAccounts(): void
    {
        $asset = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'code' => '5000',
                'name' => 'Inventory',
                'account_type' => 'asset',
                'is_group' => true,
            ]))
            ->post('/api/chart-of-accounts');
        $asset->assertStatus(201);
        $assetId = $this->getJsonFromResponse($asset)['data']['id'];

        $liab = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'code' => '6000',
                'name' => 'Liabilities',
                'account_type' => 'liability',
                'is_group' => true,
            ]))
            ->post('/api/chart-of-accounts');
        $liab->assertStatus(201);
        $liabId = $this->getJsonFromResponse($liab)['data']['id'];

        $assetLeaf = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'code' => '5100',
                'name' => 'Stock In',
                'account_type' => 'asset',
                'parent_id' => $assetId,
            ]))
            ->post('/api/chart-of-accounts');
        $assetLeaf->assertStatus(201);
        $this->debitAccountId = $this->getJsonFromResponse($assetLeaf)['data']['id'];

        $liabLeaf = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'code' => '6100',
                'name' => 'Accounts Payable - PI',
                'account_type' => 'liability',
                'parent_id' => $liabId,
            ]))
            ->post('/api/chart-of-accounts');
        $liabLeaf->assertStatus(201);
        $this->creditAccountId = $this->getJsonFromResponse($liabLeaf)['data']['id'];
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
