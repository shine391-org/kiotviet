<?php
namespace Tests\Services;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use App\Services\{ServiceName};

/**
 * {ServiceName} unit tests
 * 
 * @agent-test: {ServiceName}
 * @agent-copied-from: TESTING-PATTERNS.md
 */
class {ServiceName}Test extends CIUnitTestCase
{
    private {ServiceName} $service;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetSchema();
        $this->service = new {ServiceName}();
    }

    /** @test */
    public function it_lists_items_with_pagination()
    {
        // TODO: Implement
    }

    /** @test */
    public function it_creates_item_with_valid_data()
    {
        // TODO: Implement
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(\InvalidArgumentException::class);
        // TODO: Call create with empty data
    }

    // Helper methods
    private function resetSchema(): void
    {
        // TODO: Create tables for tests (no DROP DATABASE/TABLE; use create + truncate/delete)
    }

    private function seedItem(array $data): int
    {
        // TODO: Insert test data
    }
}
