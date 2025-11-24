<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate webhook subscription inputs.
 *
 * @agent-validator: Webhook subscriptions
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class WebhookSubscriptionValidator
{
    private const EVENTS = [
        'order.created',
        'order.confirmed',
        'order.processing',
        'order.shipping',
        'order.delivered',
        'order.completed',
        'order.cancelled',
        'return.requested',
        'return.approved',
        'return.rejected',
        'return.completed',
        'invoice.generated',
        'invoice.overdue',
        'inventory.low_stock',
        'inventory.out_of_stock',
    ];

    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    /** Validate list filters. @agent-use: GET /api/webhooks/subscriptions */
    public function validateList(array $input): array
    {
        $data = array_merge(['page' => 1, 'limit' => 20], $input);
        $rules = [
            'page' => 'permit_empty|integer|greater_than_equal_to[1]',
            'limit' => 'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[200]',
            'event' => 'permit_empty|string|max_length[100]',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
        ];

        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException($this->firstError());
        }

        $validated = $this->v->getValidated();
        $validated['page'] = (int) ($validated['page'] ?? 1);
        $validated['limit'] = (int) ($validated['limit'] ?? 20);
        $validated['event'] = isset($validated['event']) ? $this->normalizeEvent($validated['event']) : null;
        $validated['is_active'] = array_key_exists('is_active', $validated)
            ? (bool) filter_var($validated['is_active'], FILTER_VALIDATE_BOOLEAN)
            : null;

        if ($validated['event'] && ! in_array($validated['event'], self::EVENTS, true)) {
            throw new InvalidArgumentException('Event not supported');
        }

        return $validated;
    }

    /** Validate create payload. @agent-use: POST /api/webhooks/subscriptions */
    public function validateCreate(array $input): array
    {
        $event = $this->normalizeEvent($input['event'] ?? '');
        if ($event === '' || ! in_array($event, self::EVENTS, true)) {
            throw new InvalidArgumentException('event is required and must be supported');
        }

        $url = trim((string) ($input['target_url'] ?? ''));
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('target_url must be a valid URL');
        }

        $secret = isset($input['secret']) ? trim((string) $input['secret']) : null;
        if ($secret !== null && strlen($secret) > 255) {
            throw new InvalidArgumentException('secret is too long (max 255 chars)');
        }

        return [
            'event' => $event,
            'target_url' => $url,
            'secret' => $secret ?: null,
            'is_active' => array_key_exists('is_active', $input)
                ? (bool) filter_var($input['is_active'], FILTER_VALIDATE_BOOLEAN)
                : true,
        ];
    }

    /** Validate update payload. @agent-use: PUT /api/webhooks/subscriptions/{id} */
    public function validateUpdate(array $input): array
    {
        $data = [];
        if (array_key_exists('event', $input)) {
            $event = $this->normalizeEvent($input['event']);
            if ($event === '' || ! in_array($event, self::EVENTS, true)) {
                throw new InvalidArgumentException('event is required and must be supported');
            }
            $data['event'] = $event;
        }

        if (array_key_exists('target_url', $input)) {
            $url = trim((string) $input['target_url']);
            if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('target_url must be a valid URL');
            }
            $data['target_url'] = $url;
        }

        if (array_key_exists('secret', $input)) {
            $secret = $input['secret'];
            if ($secret !== null) {
                $secret = trim((string) $secret);
                if (strlen($secret) > 255) {
                    throw new InvalidArgumentException('secret is too long (max 255 chars)');
                }
            }
            $data['secret'] = $secret ?: null;
        }

        if (array_key_exists('is_active', $input)) {
            $data['is_active'] = (bool) filter_var($input['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        if (empty($data)) {
            throw new InvalidArgumentException('No fields to update');
        }

        return $data;
    }

    /** Normalize event name to dot-lowercase. */
    private function normalizeEvent(string $event): string
    {
        $event = strtolower(trim($event));
        $event = str_replace([' ', '_'], '.', $event);
        return $event;
    }

    private function firstError(): string
    {
        $errors = array_filter($this->v->getErrors());
        return $errors ? reset($errors) : 'Invalid input';
    }
}
