<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\Database\PriceListSchemaTrait;

/** @agent-test: Price list API auth/validation */
class PriceListApiAuthTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use PriceListSchemaTrait;

    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('tests');
        $this->resetPriceListSchema();
    }

    /** @test */
    public function it_rejects_missing_name_on_create()
    {
        $res = $this->withBody(json_encode(['type' => 'vip']), 'application/json')->post('api/price-lists');
        $res->assertStatus(400);
    }

    /** @test */
    public function it_returns_404_on_update_missing()
    {
        $res = $this->withBody(json_encode(['name' => 'Nope']), 'application/json')->put('api/price-lists/99999');
        $res->assertStatus(404);
    }

    /** @test */
    public function it_returns_404_on_delete_missing()
    {
        $res = $this->delete('api/price-lists/99999');
        $res->assertStatus(404);
    }

    /** @test */
    public function it_rejects_circular_reference_on_create()
    {
        // create base list
        $base = $this->withBody(json_encode(['name' => 'A']), 'application/json')
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post('api/price-lists');
        $base->assertStatus(201);
        $idA = (int) $this->db->table('db_price_lists')->where('name', 'A')->get()->getRow('id');
        $this->assertNotNull($idA);

        // create B referencing A
        $b = $this->withBody(json_encode(['name' => 'B', 'base_price_list_id' => $idA]), 'application/json')
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post('api/price-lists');
        $b->assertStatus(201);
        $idB = (int) $this->db->table('db_price_lists')->where('name', 'B')->get()->getRow('id');
        $this->assertNotNull($idB);

        // update A to reference B -> should 400
        $res = $this->withBody(json_encode(['base_price_list_id' => $idB]), 'application/json')->put("api/price-lists/{$idA}");
        $res->assertStatus(400);
    }
}
