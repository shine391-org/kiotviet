<?php

namespace Tests\Validators;

use App\Validators\AssetValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @agent-test: AssetValidator
 * @agent-pattern: Validator unit tests
 */
class AssetValidatorTest extends CIUnitTestCase
{
    private AssetValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new AssetValidator();
    }

    public function testValidateCreateWithRequiredFieldsOnly(): void
    {
        $data = ['asset_name' => 'Laptop Dell XPS'];
        $result = $this->validator->validateCreate($data);

        $this->assertEquals('Laptop Dell XPS', $result['asset_name']);
        $this->assertEquals('draft', $result['status']);
        $this->assertEquals(0.0, $result['cost']);
        $this->assertEquals(0.0, $result['salvage_value']);
        $this->assertEquals(0, $result['useful_life_months']);
    }

    public function testValidateCreateWithAllFields(): void
    {
        $data = [
            'asset_name' => 'Office Printer',
            'category' => 'Office Equipment',
            'purchase_date' => '2024-01-15',
            'cost' => 5000000,
            'location' => 'Building A - Floor 2',
            'status' => 'active',
            'salvage_value' => 500000,
            'useful_life_months' => 60,
            'created_by' => 1,
        ];
        $result = $this->validator->validateCreate($data);

        $this->assertEquals('Office Printer', $result['asset_name']);
        $this->assertEquals('Office Equipment', $result['category']);
        $this->assertEquals('2024-01-15', $result['purchase_date']);
        $this->assertEquals(5000000.0, $result['cost']);
        $this->assertEquals('Building A - Floor 2', $result['location']);
        $this->assertEquals('active', $result['status']);
        $this->assertEquals(500000.0, $result['salvage_value']);
        $this->assertEquals(60, $result['useful_life_months']);
        $this->assertEquals(1, $result['created_by']);
    }

    public function testValidateCreateThrowsOnMissingAssetName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([]);
    }

    public function testValidateCreateThrowsOnEmptyAssetName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate(['asset_name' => '']);
    }

    public function testValidateCreateThrowsOnTooLongAssetName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate(['asset_name' => str_repeat('A', 300)]);
    }

    public function testValidateCreateThrowsOnInvalidPurchaseDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'asset_name' => 'Test',
            'purchase_date' => 'invalid-date',
        ]);
    }

    public function testValidateCreateThrowsOnInvalidCost(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'asset_name' => 'Test',
            'cost' => 'not-a-number',
        ]);
    }

    public function testValidateCreateThrowsOnNegativeUsefulLifeMonths(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'asset_name' => 'Test',
            'useful_life_months' => -1,
        ]);
    }

    public function testValidateStatusSuccess(): void
    {
        $allowedStatuses = ['draft', 'active', 'disposed', 'maintenance', 'retired'];
        foreach ($allowedStatuses as $status) {
            $result = $this->validator->validateStatus(['status' => $status]);
            $this->assertEquals($status, $result['status']);
        }
    }

    public function testValidateStatusThrowsOnMissingStatus(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateStatus([]);
    }

    public function testValidateStatusThrowsOnInvalidStatus(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid asset status');
        $this->validator->validateStatus(['status' => 'invalid']);
    }

    public function testValidateCreateWithZeroValues(): void
    {
        $data = [
            'asset_name' => 'Free Asset',
            'cost' => 0,
            'salvage_value' => 0,
            'useful_life_months' => 0,
        ];
        $result = $this->validator->validateCreate($data);

        $this->assertEquals(0.0, $result['cost']);
        $this->assertEquals(0.0, $result['salvage_value']);
        $this->assertEquals(0, $result['useful_life_months']);
    }

    public function testValidateCreateCastsNumericalValues(): void
    {
        $data = [
            'asset_name' => 'Test Asset',
            'cost' => '1500000',
            'salvage_value' => '150000',
            'useful_life_months' => '36',
        ];
        $result = $this->validator->validateCreate($data);

        $this->assertIsFloat($result['cost']);
        $this->assertIsFloat($result['salvage_value']);
        $this->assertIsInt($result['useful_life_months']);
    }

    public function testValidateCreateWithLongCategory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'asset_name' => 'Test',
            'category' => str_repeat('A', 150),
        ]);
    }

    public function testValidateCreateWithLongLocation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'asset_name' => 'Test',
            'location' => str_repeat('A', 300),
        ]);
    }
}
