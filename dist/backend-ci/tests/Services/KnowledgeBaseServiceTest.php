<?php

namespace Tests\Services;

use App\Services\Portal\KnowledgeBaseService;
use App\Repositories\Portal\KnowledgeBaseRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: KnowledgeBaseService
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class KnowledgeBaseServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private KnowledgeBaseService $service;
    private KnowledgeBaseRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->repo = new KnowledgeBaseRepository(null, null, $this->db);
        $this->service = new KnowledgeBaseService($this->repo);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_lists_published_articles()
    {
        $cat = $this->repo->createCategory(['name' => 'General', 'description' => '']);
        $this->repo->createArticle(['category_id' => $cat['id'], 'title' => 'Welcome', 'content' => 'Hello', 'is_published' => 1]);
        $this->repo->createArticle(['category_id' => $cat['id'], 'title' => 'Draft', 'content' => 'Not shown', 'is_published' => 0]);

        $articles = $this->service->articles([])['data'];
        $this->assertCount(1, $articles);
        $this->assertEquals('Welcome', $articles[0]['title']);
    }
}
