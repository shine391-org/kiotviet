<?php

namespace Tests\Validators;

use App\Validators\ProductMediaSearchValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @agent-test: ProductMediaSearchValidator
 * @agent-pattern: Validator unit coverage
 */
class ProductMediaSearchValidatorTest extends CIUnitTestCase
{
    private ProductMediaSearchValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ProductMediaSearchValidator();
    }

    public function testValidateTrimsAndCastsValues(): void
    {
        $result = $this->validator->validate([
            'sku' => '  abc  ',
            'limit' => '30',
            'offset' => '5',
            'entity_id' => '7',
        ]);

        $this->assertSame('abc', $result['sku']);
        $this->assertSame(30, $result['limit']);
        $this->assertSame(5, $result['offset']);
        $this->assertSame(7, $result['entity_id']);
    }

    public function testValidateUsesDefaultsWhenMissing(): void
    {
        $result = $this->validator->validate([]);

        $this->assertSame('', $result['sku']);
        $this->assertSame(20, $result['limit']);
        $this->assertSame(0, $result['offset']);
        $this->assertNull($result['entity_id']);
    }

    public function testValidateRejectsInvalidLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validate(['limit' => 0]);
    }
}
