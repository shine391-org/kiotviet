<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/** @agent-test: Price list API auth/validation */
class PriceListApiAuthTest extends CIUnitTestCase
{
    use FeatureTestTrait;

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
}
