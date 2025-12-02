<?php

namespace Tests\Services;

use App\Services\Accounting\COAService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: COAService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class COAServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private COAService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->service = new COAService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_root_group_and_child_account()
    {
        $root = $this->service->create([
            'code' => '1000',
            'name' => 'Assets',
            'account_type' => 'asset',
            'is_group' => true,
        ])['data'];

        $child = $this->service->create([
            'code' => '1100',
            'name' => 'Cash',
            'account_type' => 'asset',
            'parent_id' => $root['id'],
        ])['data'];

        $this->assertEquals($root['id'], $child['parent_id']);
        $this->assertFalse($child['is_group']);
    }

    /** @test */
    public function it_requires_parent_to_be_group()
    {
        $parent = $this->service->create([
            'code' => '2000',
            'name' => 'Liabilities',
            'account_type' => 'liability',
            'is_group' => false,
        ])['data'];

        $this->expectException(\RuntimeException::class);
        $this->service->create([
            'code' => '2100',
            'name' => 'Payable',
            'account_type' => 'liability',
            'parent_id' => $parent['id'],
        ]);
    }

    /** @test */
    public function it_validates_account_type()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->create([
            'code' => '9999',
            'name' => 'Invalid',
            'account_type' => 'weird',
        ]);
    }
}
