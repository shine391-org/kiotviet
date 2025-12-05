<?php

namespace Tests\Services;

use App\Services\Portal\PortalService;
use App\Repositories\Portal\PortalUserRepository;
use App\Validators\PortalValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: PortalService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class PortalServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private PortalService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $repo = new PortalUserRepository(null, null, $this->db);
        $this->service = new PortalService($repo, new PortalValidator());
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_logs_in_and_verifies_token()
    {
        $user = $this->service->createUser(['email' => 'a@b.com', 'password' => 'pass'])['data'];
        $login = $this->service->login(['email' => 'a@b.com', 'password' => 'pass'])['data'];
        $this->assertArrayHasKey('token', $login);

        $verified = $this->service->verifyToken($login['token']);
        $this->assertEquals($user['email'], $verified['email']);
    }
}
