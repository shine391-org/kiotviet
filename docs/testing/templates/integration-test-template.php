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
        $username = getenv('TEST_USER') ?: 'devadmin';
        $password = getenv('TEST_PASS') ?: 'Admin@123';
        
        $response = $this->post('/api/auth/login', [
            'username' => $username,
            'password' => $password
        ]);
        
        // Validate response status
        if ($response->getStatusCode() !== 200) {
            $this->fail('Login failed with status: ' . $response->getStatusCode());
        }
        
        // Validate JSON response
        $data = json_decode($response->getBody(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->fail('Invalid JSON response from login: ' . json_last_error_msg());
        }
        
        // Validate token exists
        if (!isset($data['token']) || empty($data['token'])) {
            $this->fail('Login response missing token. Response: ' . print_r($data, true));
        }
        
        return $data['token'];
    }

    private function cleanupTestData(): void
    {
        // TODO: Clean test data without DROP DATABASE/TABLE (use truncate/delete inside transaction)
    }
}
