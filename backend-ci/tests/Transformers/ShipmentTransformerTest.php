<?php

namespace Tests\Transformers;

use App\Transformers\ShipmentTransformer;
use CodeIgniter\Test\CIUnitTestCase;

class ShipmentTransformerTest extends CIUnitTestCase
{
    private ShipmentTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new ShipmentTransformer();
    }

    public function testTransformBasicFields(): void
    {
        $row = ['id' => 1, 'invoice_code' => 'INV001', 'customer_name' => 'John'];
        $result = $this->transformer->transform($row);
        
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('INV001', $result['invoice_code']);
        $this->assertEquals('John', $result['customer_name']);
    }

    public function testTransformDefaultCustomerName(): void
    {
        $row = ['id' => 1];
        $result = $this->transformer->transform($row);
        
        $this->assertEquals('Khách lẻ', $result['customer_name']);
    }

    public function testTransformDeliveryPartnerName(): void
    {
        $row = ['id' => 1, 'delivery_partner' => 'ghn'];
        $result = $this->transformer->transform($row);
        
        $this->assertEquals('ghn', $result['delivery_partner']);
        $this->assertEquals('Giao hàng nhanh', $result['delivery_partner_name']);
    }

    public function testTransformManualDeliveryPartner(): void
    {
        $row = ['id' => 1, 'delivery_partner' => 'manual'];
        $result = $this->transformer->transform($row);
        
        $this->assertEquals('Tự giao', $result['delivery_partner_name']);
    }

    public function testTransformUnknownDeliveryPartner(): void
    {
        $row = ['id' => 1, 'delivery_partner' => 'custom'];
        $result = $this->transformer->transform($row);
        
        $this->assertEquals('Custom', $result['delivery_partner_name']);
    }

    public function testTransformCodCalculation(): void
    {
        $row = ['id' => 1, 'cod_amount' => 100000, 'cod_collected' => 30000];
        $result = $this->transformer->transform($row);
        
        $this->assertEquals(100000, $result['cod_amount']);
        $this->assertEquals(70000, $result['cod_remaining']);
    }

    public function testTransformCodRemainingNotNegative(): void
    {
        $row = ['id' => 1, 'cod_amount' => 100000, 'cod_collected' => 150000];
        $result = $this->transformer->transform($row);
        
        $this->assertEquals(0, $result['cod_remaining']);
    }

    public function testTransformDefaultDeliveryStatus(): void
    {
        $row = ['id' => 1];
        $result = $this->transformer->transform($row);
        
        $this->assertEquals('pending', $result['delivery_status']);
    }

    public function testTransformAreaPath(): void
    {
        $row = ['id' => 1, 'area_province' => 'Ha Noi', 'area_district' => 'Hoan Kiem'];
        $result = $this->transformer->transform($row);
        
        $this->assertEquals(['Ha Noi', 'Hoan Kiem'], $result['area_path']);
    }

    public function testTransformEmptyAreaPath(): void
    {
        $row = ['id' => 1];
        $result = $this->transformer->transform($row);
        
        $this->assertEmpty($result['area_path']);
    }

    public function testTransformDeliveryHistory(): void
    {
        $row = [
            'id' => 1,
            'created_at' => '2024-01-01 10:00:00',
            'delivery_partner' => 'ghn',
            'delivery_status' => 'delivered',
            'delivery_time' => '2024-01-02 14:00:00',
        ];
        $result = $this->transformer->transform($row);
        
        $this->assertCount(2, $result['delivery_history']);
        $this->assertEquals('pending', $result['delivery_history'][0]['status']);
        $this->assertEquals('delivered', $result['delivery_history'][1]['status']);
    }

    public function testTransformListTransformsAllRows(): void
    {
        $rows = [
            ['id' => 1, 'customer_name' => 'John'],
            ['id' => 2, 'customer_name' => 'Jane'],
        ];
        $result = $this->transformer->transformList($rows);
        
        $this->assertCount(2, $result);
        $this->assertEquals('John', $result[0]['customer_name']);
        $this->assertEquals('Jane', $result[1]['customer_name']);
    }

    public function testTransformGeneratesCodeWhenMissing(): void
    {
        $row = ['id' => 123, 'created_at' => '2024-01-01'];
        $result = $this->transformer->transform($row);
        
        $this->assertNotEmpty($result['code']);
        $this->assertEquals(8, strlen($result['code']));
    }

    public function testTransformCastsBranchId(): void
    {
        $row = ['id' => 1, 'branch_id' => '5'];
        $result = $this->transformer->transform($row);
        
        $this->assertIsInt($result['branch_id']);
        $this->assertEquals(5, $result['branch_id']);
    }

    public function testTransformDefaultDimensions(): void
    {
        $row = ['id' => 1];
        $result = $this->transformer->transform($row);
        
        $this->assertEquals(['length' => 10, 'width' => 10, 'height' => 10], $result['dimensions']);
    }
}
