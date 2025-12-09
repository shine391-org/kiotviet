<?php

namespace Tests\Validators;

use App\Validators\OrderPaymentValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @agent-test: OrderPaymentValidator
 * @agent-pattern: Validator unit coverage
 */
class OrderPaymentValidatorTest extends CIUnitTestCase
{
    private OrderPaymentValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new OrderPaymentValidator();
    }

    public function testValidateCreateRequiresOrderId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'payment_method' => 'CASH',
            'amount' => 100000,
        ]);
    }

    public function testValidateCreateRequiresPaymentMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'order_id' => 1,
            'amount' => 100000,
        ]);
    }

    public function testValidateCreateRequiresAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'order_id' => 1,
            'payment_method' => 'CASH',
        ]);
    }

    public function testValidateCreateRequiresValidPaymentMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'order_id' => 1,
            'payment_method' => 'INVALID',
            'amount' => 100000,
        ]);
    }

    public function testValidateCreateRequiresPositiveAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'order_id' => 1,
            'payment_method' => 'CASH',
            'amount' => 0,
        ]);
    }

    public function testValidateCreateAcceptsValidData(): void
    {
        $data = [
            'order_id' => 1,
            'payment_method' => 'CASH',
            'amount' => 100000,
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame(1, $result['order_id']);
        $this->assertSame('CASH', $result['payment_method']);
        $this->assertSame(100000.0, $result['amount']);
    }

    public function testValidateCreateAcceptsBankTransfer(): void
    {
        $data = [
            'order_id' => 1,
            'payment_method' => 'BANK_TRANSFER',
            'amount' => 500000,
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame('BANK_TRANSFER', $result['payment_method']);
    }

    public function testValidateCreateAcceptsCard(): void
    {
        $data = [
            'order_id' => 1,
            'payment_method' => 'CARD',
            'amount' => 200000,
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame('CARD', $result['payment_method']);
    }

    public function testValidateCreateAcceptsCOD(): void
    {
        $data = [
            'order_id' => 1,
            'payment_method' => 'COD',
            'amount' => 300000,
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame('COD', $result['payment_method']);
    }

    public function testValidateCreateAcceptsEwallet(): void
    {
        $data = [
            'order_id' => 1,
            'payment_method' => 'EWALLET',
            'amount' => 150000,
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame('EWALLET', $result['payment_method']);
    }

    public function testValidateCreateWithPaidAt(): void
    {
        $data = [
            'order_id' => 1,
            'payment_method' => 'CASH',
            'amount' => 100000,
            'paid_at' => '2024-01-15',
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame('2024-01-15', $result['paid_at']);
    }

    public function testValidateCreateDefaultPaidAtIsNull(): void
    {
        $data = [
            'order_id' => 1,
            'payment_method' => 'CASH',
            'amount' => 100000,
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertNull($result['paid_at']);
    }
}
