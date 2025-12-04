<?php

namespace Tests\Validators;

use App\Validators\WebhookPayloadValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * Exercise the webhook payload validation rules.
 *
 * @agent-test: WebhookPayloadValidator
 * @agent-pattern: Validator unit coverage
 * @agent-reusable: MEDIUM
 */
class WebhookPayloadValidatorTest extends CIUnitTestCase
{
    private WebhookPayloadValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new WebhookPayloadValidator();
    }

    /**
     * @agent-use: Ensure valid product payloads pass and normalize casing
     * @agent-pattern: Happy-path validator
     */
    public function testValidateProductReturnsNormalizedData(): void
    {
        $payload = [
            'idempotency_key' => 'prod-123',
            'event' => 'PRODUCT.CREATED',
            'data' => [
                'code' => '  SKU-1  ',
                'name' => 'Test product',
                'price' => 150000,
            ],
        ];

        $result = $this->validator->validateProduct($payload);

        $this->assertSame('prod-123', $result['idempotency_key']);
        $this->assertSame('product.created', $result['event']);
        $this->assertSame('SKU-1', $result['data']['code']);
        $this->assertSame('Test product', $result['data']['name']);
        $this->assertSame(150000.0, $result['data']['price']);
    }

    /**
     * @agent-use: Guard required product payload sections
     * @agent-pattern: Failure on missing nested data
     */
    public function testValidateProductRequiresData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('data is required');

        $this->validator->validateProduct([
            'idempotency_key' => 'missing-data',
            'event' => 'webhook',
        ]);
    }

    /**
     * @agent-use: Confirm items are normalized and defaults are applied
     * @agent-pattern: Complex embedded array validation
     */
    public function testValidateOrderNormalizesItems(): void
    {
        $payload = [
            'idempotency_key' => 'order-1',
            'event' => 'ORDER.CREATED',
            'data' => [
                'payment_method' => null,
                'branch_id' => null,
                'items' => [
                    [
                        'product_code' => ' CODE-1 ',
                        'quantity' => '2',
                        'price' => '50000',
                    ],
                ],
            ],
        ];

        $result = $this->validator->validateOrder($payload);

        $this->assertSame('order-1', $result['idempotency_key']);
        $this->assertSame('order.created', $result['event']);
        $this->assertSame('CASH', $result['data']['payment_method']);
        $this->assertSame(1, $result['data']['branch_id']);
        $this->assertCount(1, $result['data']['items']);
        $item = $result['data']['items'][0];
        $this->assertSame('CODE-1', $item['product_code']);
        $this->assertSame(2.0, $item['quantity']);
        $this->assertSame(50000.0, $item['price']);
    }

    /**
     * @agent-use: Validate presence of product_code
     * @agent-pattern: Error path verification
     */
    public function testValidateOrderRequiresProductCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('product_code required for item 0');

        $payload = [
            'idempotency_key' => 'order-2',
            'event' => 'order.created',
            'data' => [
                'items' => [
                    ['quantity' => 1, 'product_code' => ''],
                ],
            ],
        ];

        $this->validator->validateOrder($payload);
    }

    /**
     * @agent-use: Confirm quantity must be positive
     * @agent-pattern: Boundary validation path
     */
    public function testValidateOrderRejectsNonPositiveQuantity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('quantity must be > 0 for item 0');

        $payload = [
            'idempotency_key' => 'order-3',
            'event' => 'order.created',
            'data' => [
                'items' => [
                    ['product_code' => 'SKU-2', 'quantity' => 0],
                ],
            ],
        ];

        $this->validator->validateOrder($payload);
    }
}
