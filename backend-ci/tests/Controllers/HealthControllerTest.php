<?php

namespace Tests\Controllers;

use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

class HealthControllerTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->get('api/health');
        $response->assertStatus(200);
        $response->assertJSONFragment(['status' => 'ok']);
        $response->assertJSONPath('time', fn($time) => is_numeric($time));
    }
}
