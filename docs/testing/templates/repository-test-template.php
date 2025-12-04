<?php
namespace Tests\Repositories;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use App\Repositories\{RepositoryName};

/**
 * {RepositoryName} unit tests
 * 
 * @agent-test: {RepositoryName}
 * @agent-copied-from: TESTING-PATTERNS-01
 */
class {RepositoryName}Test extends CIUnitTestCase
{
    private {RepositoryName} $repo;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetSchema();
        $this->repo = new {RepositoryName}();
    }

    /** @test */
    public function it_finds_all_active_items()
    {
        // TODO: Implement
    }

    /** @test */
    public function it_searches_by_criteria()
    {
        // TODO: Implement
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
