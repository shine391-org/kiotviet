<?php

namespace Tests\Services;

use App\Repositories\Payments\PaymentEntryRepository;
use App\Services\Payments\PaymentEntryService;
use App\Validators\PaymentEntryValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: PaymentEntryService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class PaymentEntryServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private PaymentEntryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $repo = new PaymentEntryRepository(null, $this->db);
        $this->service = new PaymentEntryService($repo, new PaymentEntryValidator());
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_is_idempotent_by_order_method_reference()
    {
        $first = $this->service->create([
            'order_id' => 1,
            'payment_method' => 'CASH',
            'amount' => 100,
            'reference' => 'ref1',
        ]);
        $second = $this->service->create([
            'order_id' => 1,
            'payment_method' => 'CASH',
            'amount' => 100,
            'reference' => 'ref1',
        ]);

        $this->assertEquals($first['data']['id'], $second['data']['id']);
        $this->assertEquals(1, $this->db->table('payment_entries')->countAllResults());
    }

    /** @test */
    public function it_updates_status_on_refund()
    {
        $entry = $this->service->create([
            'order_id' => 2,
            'payment_method' => 'CARD',
            'amount' => 50,
        ])['data'];
        $this->service->refund($entry['id']);
        $row = $this->db->table('payment_entries')->where('id', $entry['id'])->get()->getRowArray();
        $this->assertEquals('refunded', $row['status']);
    }
}
