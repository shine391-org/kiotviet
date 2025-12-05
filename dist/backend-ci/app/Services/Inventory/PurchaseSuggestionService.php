<?php

namespace App\Services\Inventory;

use App\Repositories\Inventory\PurchaseSuggestionRepository;
use App\Validators\PurchaseSuggestionValidator;
use InvalidArgumentException;

/**
 * Purchase suggestion lifecycle (acknowledge, convert).
 *
 * @agent-service: Purchase suggestions
 * @agent-pattern: Status transitions
 * @agent-reusable: MEDIUM
 */
class PurchaseSuggestionService
{
    protected PurchaseSuggestionRepository $suggestions;
    protected PurchaseSuggestionValidator $validator;

    public function __construct(
        ?PurchaseSuggestionRepository $suggestions = null,
        ?PurchaseSuggestionValidator $validator = null
    ) {
        $this->suggestions = $suggestions ?? new PurchaseSuggestionRepository();
        $this->validator = $validator ?? new PurchaseSuggestionValidator();
    }

    /**
     * List purchase suggestions.
     * @agent-use: GET /api/purchase-suggestions
     * @agent-pattern: Delegate listing
     */
    public function list(array $filters): array
    {
        $validated = $this->validator->validateList($filters);
        return ['success' => true, 'data' => $this->suggestions->list($validated)];
    }

    /**
     * Acknowledge a suggestion.
     * @agent-use: POST /api/purchase-suggestions/{id}/ack
     * @agent-pattern: Status update
     */
    public function acknowledge(int $id, array $data): array
    {
        $payload = $this->validator->validateAcknowledge($data);
        $suggestion = $this->requireSuggestion($id);
        if (($suggestion['status'] ?? '') === 'converted') {
            throw new InvalidArgumentException('Suggestion already converted');
        }
        $update = [
            'status' => 'acknowledged',
            'acknowledged_by' => $payload['acknowledged_by'] ?? null,
            'acknowledged_at' => date('Y-m-d H:i:s'),
        ];
        $updated = $this->suggestions->update($id, $update);
        return ['success' => true, 'data' => $updated];
    }

    /**
     * Convert suggestion into purchase order stub.
     * @agent-use: POST /api/purchase-suggestions/{id}/convert
     * @agent-pattern: Conversion to PO
     */
    public function convertToPurchaseOrder(int $id, array $data): array
    {
        $payload = $this->validator->validateConvert($data);
        $suggestion = $this->requireSuggestion($id);
        if (($suggestion['status'] ?? '') === 'converted') {
            return ['success' => true, 'data' => $suggestion];
        }

        $poId = $this->createPurchaseOrder($suggestion, $payload['purchase_order_code'] ?? null);
        $update = [
            'status' => 'converted',
            'purchase_order_id' => $poId,
            'converted_at' => date('Y-m-d H:i:s'),
        ];
        if (! empty($payload['acknowledged_by'])) {
            $update['acknowledged_by'] = (int) $payload['acknowledged_by'];
            $update['acknowledged_at'] = $update['converted_at'];
        }
        $updated = $this->suggestions->update($id, $update);

        return [
            'success' => true,
            'data' => $updated,
            'purchase_order_id' => $poId,
        ];
    }

    private function createPurchaseOrder(array $suggestion, ?string $code = null): int
    {
        $db = $this->suggestions->db();
        $payload = [
            'branch_id' => $suggestion['branch_id'] ?? null,
            'status' => 'draft',
            'total' => 0,
            'code' => $code ?: ('PO-SUG-' . date('YmdHis') . '-' . random_int(1000, 9999)),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $db->table('purchase_orders')->insert($payload);
        return (int) $db->insertID();
    }

    private function requireSuggestion(int $id): array
    {
        $row = $this->suggestions->find($id);
        if (! $row) {
            throw new InvalidArgumentException('Suggestion not found');
        }
        return $row;
    }
}
