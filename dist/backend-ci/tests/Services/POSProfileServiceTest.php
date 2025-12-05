<?php

namespace Tests\Services;

use App\Services\POS\POSProfileService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\POSSchemaTrait;

/**
 * @agent-test: POSProfileService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait + POSSchemaTrait
 */
class POSProfileServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use POSSchemaTrait;

    private POSProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetPOSSchema();
        $this->seedBase();
        $this->service = new POSProfileService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_profile_and_resolves_by_user()
    {
        $create = $this->service->create([
            'name' => 'Main POS',
            'user_id' => 1,
            'branch_id' => 1,
            'price_list_id' => 1,
            'payment_methods' => ['CASH', 'CARD'],
            'require_shift' => true,
        ]);

        $this->assertTrue($create['success']);
        $profile = $create['data'];
        $this->assertEquals(1, $profile['branch_id']);
        $this->assertCount(2, $profile['payment_methods']);

        $resolved = $this->service->resolve(['user_id' => 1, 'branch_id' => 1]);
        $this->assertTrue($resolved['success']);
        $this->assertEquals($profile['id'], $resolved['data']['id']);
    }

    /** @test */
    public function it_rejects_unknown_payment_method()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->create([
            'name' => 'Invalid',
            'user_id' => 1,
            'branch_id' => 1,
            'payment_methods' => ['UNKNOWN'],
        ]);
    }

    private function seedBase(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('branches')->insert(['id' => 1, 'name' => 'Main', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('payment_methods')->insertBatch([
            ['code' => 'CASH', 'name' => 'Cash', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'CARD', 'name' => 'Card', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
        $this->db->table('price_lists')->insert([
            'id' => 1,
            'name' => 'POS List',
            'type' => 'custom',
            'priority' => 1,
            'is_active' => 1,
            'start_date' => date('Y-m-d', strtotime('-1 day')),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
