<?php
namespace Tests\Integration\Api;

use CodeIgniter\Test\FeatureTestCase;
use Config\Database;

/**
 * {Module} API integration tests
 * 
 * @agent-test: {Module} API
 * @agent-copied-from: TESTING-PATTERNS.md
 */
class {Module}ApiTest extends FeatureTestCase
{
    protected $db;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->token = $this->getAuthToken();
        $this->cleanupTestData();
    }

    /** @test */
    public function it_creates_item_via_api()
    {
        // TODO: Implement
    }

    /** @test */
    public function it_requires_authentication()
    {
        // TODO: Test without token
    }

    // Helper methods
    private function getAuthToken(): string
    {
        $response = $this->post('/api/auth/login', [
            'username' => 'devadmin',
            'password' => 'Admin@123'
        ]);
        $data = json_decode($response->getBody(), true);
        return $data['token'] ?? '';
    }

    private function cleanupTestData(): void
    {
        // TODO: Clean test data
    }
}
