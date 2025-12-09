<?php

namespace Tests\Validators;

use App\Validators\POSProfileValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @agent-test: POSProfileValidator
 * @agent-pattern: Validator unit coverage
 */
class POSProfileValidatorTest extends CIUnitTestCase
{
    private POSProfileValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new POSProfileValidator();
    }

    public function testValidateCreateRequiresName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'branch_id' => 1,
        ]);
    }

    public function testValidateCreateRequiresBranchId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'name' => 'POS 1',
        ]);
    }

    public function testValidateCreateAcceptsValidData(): void
    {
        $data = [
            'name' => 'POS 1',
            'branch_id' => 1,
            'payment_methods' => ['CASH'],
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame('POS 1', $result['name']);
        $this->assertSame(1, $result['branch_id']);
        $this->assertSame('active', $result['status']);
    }

    public function testValidateCreateSetsDefaultRequireShift(): void
    {
        $data = [
            'name' => 'POS 1',
            'branch_id' => 1,
            'payment_methods' => ['CASH'],
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertTrue($result['require_shift']);
    }

    public function testValidateCreateSetsDefaultAllowOffline(): void
    {
        $data = [
            'name' => 'POS 1',
            'branch_id' => 1,
            'payment_methods' => ['CASH'],
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertFalse($result['allow_offline']);
    }

    public function testValidateCreateWithOptionalFields(): void
    {
        $data = [
            'name' => 'POS 1',
            'branch_id' => 1,
            'user_id' => 1,
            'role_id' => 2,
            'price_list_id' => 3,
            'credit_limit' => 1000000,
            'payment_methods' => ['CASH'],
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame(1, $result['user_id']);
        $this->assertSame(2, $result['role_id']);
        $this->assertSame(3, $result['price_list_id']);
        $this->assertSame(1000000.0, $result['credit_limit']);
    }

    public function testValidateCreateNormalizesPaymentMethods(): void
    {
        $data = [
            'name' => 'POS 1',
            'branch_id' => 1,
            'payment_methods' => ['cash', 'BANK_TRANSFER'],
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame('CASH', $result['payment_methods'][0]['payment_method']);
        $this->assertTrue($result['payment_methods'][0]['is_allowed']);
    }

    public function testValidateUpdateAcceptsPartialData(): void
    {
        $data = ['name' => 'Updated POS'];

        $result = $this->validator->validateUpdate($data);

        $this->assertSame('Updated POS', $result['name']);
    }

    public function testValidateUpdateThrowsOnEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateUpdate(['name' => '']);
    }

    public function testValidateUpdateWithAllowOffline(): void
    {
        $result = $this->validator->validateUpdate(['allow_offline' => true]);

        $this->assertTrue($result['allow_offline']);
    }

    public function testValidateUpdateWithRequireShift(): void
    {
        $result = $this->validator->validateUpdate(['require_shift' => false]);

        $this->assertFalse($result['require_shift']);
    }

    public function testValidateUpdateWithNegativeCreditLimitThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateUpdate(['credit_limit' => -100]);
    }

    public function testValidateResolveRequiresUserIdOrProfileId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateResolve(['branch_id' => 1]);
    }

    public function testValidateResolveWithUserId(): void
    {
        $result = $this->validator->validateResolve(['user_id' => 1, 'branch_id' => 1]);

        $this->assertSame(1, $result['user_id']);
        $this->assertNull($result['profile_id']);
    }

    public function testValidateResolveWithProfileId(): void
    {
        $result = $this->validator->validateResolve(['user_id' => 1, 'branch_id' => 1, 'profile_id' => 2]);

        $this->assertSame(2, $result['profile_id']);
        $this->assertSame(1, $result['user_id']);
    }

    public function testValidateResolveWithBranchId(): void
    {
        $result = $this->validator->validateResolve([
            'user_id' => 1,
            'branch_id' => 2,
        ]);

        $this->assertSame(2, $result['branch_id']);
    }

    public function testPaymentMethodsAsArray(): void
    {
        $data = [
            'name' => 'POS 1',
            'branch_id' => 1,
            'payment_methods' => [
                ['payment_method' => 'CASH', 'is_allowed' => true],
                ['payment_method' => 'CARD', 'is_allowed' => false],
            ],
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertCount(2, $result['payment_methods']);
        $this->assertTrue($result['payment_methods'][0]['is_allowed']);
        $this->assertFalse($result['payment_methods'][1]['is_allowed']);
    }

    public function testPaymentMethodsEmptyThrows(): void
    {
        $data = [
            'name' => 'POS 1',
            'branch_id' => 1,
            'payment_methods' => [],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate($data);
    }

    public function testPaymentMethodsInvalidTypeThrows(): void
    {
        $data = [
            'name' => 'POS 1',
            'branch_id' => 1,
            'payment_methods' => 'CASH',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate($data);
    }
}
