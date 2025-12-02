<?php

namespace App\Repositories\DeliveryNotes;

use App\Models\DeliveryNoteItemModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Delivery note item persistence.
 *
 * @agent-repository: Delivery note items
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class DeliveryNoteItemRepository
{
    protected DeliveryNoteItemModel $items;
    protected BaseConnection $db;

    public function __construct(?DeliveryNoteItemModel $items = null, ?BaseConnection $db = null)
    {
        $this->items = $items ?? new DeliveryNoteItemModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    public function find(int $id): ?array
    {
        $row = $this->items->find($id);
        return $row ?: null;
    }

    public function byDelivery(int $deliveryNoteId): array
    {
        return $this->items->where('delivery_note_id', $deliveryNoteId)->findAll();
    }

    public function update(int $id, array $data): bool
    {
        return (bool) $this->items->update($id, $data + ['updated_at' => date('Y-m-d H:i:s')]);
    }
}
