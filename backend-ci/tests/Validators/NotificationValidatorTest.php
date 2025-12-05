<?php

namespace Tests\Validators;

use App\Validators\NotificationValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @agent-test: NotificationValidator
 * @agent-pattern: Validator unit coverage
 */
class NotificationValidatorTest extends CIUnitTestCase
{
    private NotificationValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new NotificationValidator();
    }

    public function testValidateRuleAppliesDefaults(): void
    {
        $data = [
            'name' => 'Ticket created',
            'event_type' => 'ticket.created',
            'template' => 'Template body',
        ];

        $result = $this->validator->validateRule($data);

        $this->assertSame('email', $result['channel']);
        $this->assertSame(1, $result['is_active']);
    }

    public function testValidateRuleRejectsMissingName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateRule(['event_type' => 'foo', 'template' => 'bar']);
    }

    public function testValidateTriggerAllowsOptionalFields(): void
    {
        $result = $this->validator->validateTrigger([
            'event_type' => 'ticket.created',
            'entity_id' => 5,
        ]);

        $this->assertSame('ticket.created', $result['event_type']);
        $this->assertSame(5, $result['entity_id']);
    }
}
