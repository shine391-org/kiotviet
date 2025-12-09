<?php

namespace Tests\Transformers;

use App\Transformers\PaymentMethodTransformer;
use CodeIgniter\Test\CIUnitTestCase;

class PaymentMethodTransformerTest extends CIUnitTestCase
{
    private PaymentMethodTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new PaymentMethodTransformer();
    }

    public function testTransformCastsIdToInt(): void
    {
        $row = ['id' => '123', 'code' => 'CASH'];
        $result = $this->transformer->transform($row);
        
        $this->assertIsInt($result['id']);
        $this->assertEquals(123, $result['id']);
    }

    public function testTransformHandlesNullId(): void
    {
        $row = ['code' => 'CASH'];
        $result = $this->transformer->transform($row);
        
        $this->assertNull($result['id']);
    }

    public function testTransformCastsIsActiveToBool(): void
    {
        $row = ['id' => 1, 'is_active' => 1];
        $result = $this->transformer->transform($row);
        
        $this->assertTrue($result['is_active']);
    }

    public function testTransformDefaultsIsActiveToFalse(): void
    {
        $row = ['id' => 1];
        $result = $this->transformer->transform($row);
        
        $this->assertFalse($result['is_active']);
    }

    public function testTransformCastsDisplayOrderToInt(): void
    {
        $row = ['id' => 1, 'display_order' => '5'];
        $result = $this->transformer->transform($row);
        
        $this->assertIsInt($result['display_order']);
        $this->assertEquals(5, $result['display_order']);
    }

    public function testTransformDefaultsDisplayOrderToZero(): void
    {
        $row = ['id' => 1];
        $result = $this->transformer->transform($row);
        
        $this->assertEquals(0, $result['display_order']);
    }

    public function testTransformDecodesNameTranslations(): void
    {
        $row = ['id' => 1, 'name_translations' => '{"vi":"Tiền mặt","en":"Cash"}'];
        $result = $this->transformer->transform($row);
        
        $this->assertIsArray($result['name_translations']);
        $this->assertEquals('Tiền mặt', $result['name_translations']['vi']);
        $this->assertEquals('Cash', $result['name_translations']['en']);
    }

    public function testTransformHandlesInvalidJsonTranslations(): void
    {
        $row = ['id' => 1, 'name_translations' => 'invalid'];
        $result = $this->transformer->transform($row);
        
        $this->assertNull($result['name_translations']);
    }

    public function testTransformPreservesArrayTranslations(): void
    {
        $row = ['id' => 1, 'name_translations' => ['vi' => 'Tiền mặt']];
        $result = $this->transformer->transform($row);
        
        $this->assertEquals(['vi' => 'Tiền mặt'], $result['name_translations']);
    }

    public function testTransformListTransformsAllRows(): void
    {
        $rows = [
            ['id' => '1', 'code' => 'CASH'],
            ['id' => '2', 'code' => 'TRANSFER'],
        ];
        $result = $this->transformer->transformList($rows);
        
        $this->assertCount(2, $result);
        $this->assertIsInt($result[0]['id']);
        $this->assertIsInt($result[1]['id']);
    }

    public function testTransformListEmptyArray(): void
    {
        $result = $this->transformer->transformList([]);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
