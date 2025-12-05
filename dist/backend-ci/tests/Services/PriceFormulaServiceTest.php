<?php

namespace Tests\Services;

use App\Services\PriceLists\PriceFormulaService;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use RuntimeException;

/** @agent-test: PriceFormulaService @agent-pattern: Formula parsing/calculation */
class PriceFormulaServiceTest extends CIUnitTestCase
{
    private PriceFormulaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PriceFormulaService();
    }

    /** @test */
    public function it_parses_simple_formula()
    {
        $tokens = $this->service->parseFormula('base * 0.9');
        $this->assertEquals(['base', '*', 0.9], $tokens);
    }

    /** @test */
    public function it_calculates_formula()
    {
        $result = $this->service->calculateFromFormula('base * 0.9', 100000);
        $this->assertEquals(90000, $result);
    }

    /** @test */
    public function it_detects_invalid_syntax()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->validateFormula('base ++ 10');
    }

    /** @test */
    public function it_blocks_division_by_zero()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->validateFormula('base / 0');
    }

    /** @test */
    public function it_rounds_thousand()
    {
        $this->assertEquals(123000, $this->service->applyRounding(123456, 'thousand'));
    }

    /** @test */
    public function it_rounds_ten_thousand()
    {
        $this->assertEquals(120000, $this->service->applyRounding(123456, 'ten_thousand'));
    }

    /** @test */
    public function it_rounds_hundred()
    {
        $this->assertEquals(123500, $this->service->applyRounding(123456, 'hundred'));
    }

    /** @test */
    public function it_handles_complex_formula()
    {
        $result = $this->service->calculateFromFormula('base * 0.85 - 10000', 200000);
        $this->assertEquals(160000, $result);
    }

    /** @test */
    public function it_returns_zero_for_negative_result()
    {
        $result = $this->service->calculateFromFormula('base - 200000', 100000);
        $this->assertSame(0.0, $result);
    }

    /** @test */
    public function parse_empty_returns_empty_array()
    {
        $this->assertSame([], $this->service->parseFormula('   '));
    }
}
