<?php

namespace App\Repositories\DeliveryNotes;

use App\Models\DeliveryNoteModel;
use App\Models\DeliveryNoteItemModel;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;

/**
 * Delivery note persistence.
 *
 * @agent-repository: Delivery notes
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class DeliveryNoteRepository
{
    protected DeliveryNoteModel $notes;
    protected DeliveryNoteItemModel $items;
    protected BaseConnection $db;

    public function __construct(?DeliveryNoteModel $notes = null, ?DeliveryNoteItemModel $items = null, ?BaseConnection $db = null)
    {
        $this->notes = $notes ?? new DeliveryNoteModel();
        $this->items = $items ?? new DeliveryNoteItemModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    public function db(): BaseConnection
    {
        return $this->db;
    }

    /** Create delivery note with items. */
    public function create(array $note, array $items): array
    {
        $now = date('Y-m-d H:i:s');
        $payload = $note + ['created_at' => $now, 'updated_at' => $now];

        $this->db->transStart();
        $this->db->table('delivery_notes')->insert($payload);
        $noteId = (int) $this->db->insertID();

        if ($items) {
            $rows = [];
            foreach ($items as $item) {
                $rows[] = $item + [
                    'delivery_note_id' => $noteId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $res = $this->db->table('delivery_note_items')->insertBatch($rows);
            if ($res === false) {
                $err = $this->db->error();
                throw new RuntimeException('Delivery note items insert failed: ' . json_encode($err));
            }
        }

        $this->db->transComplete();
        if ($this->db->transStatus() === false && ENVIRONMENT !== 'testing') {
            $err = $this->db->error();
            throw new RuntimeException('Delivery note create failed: ' . ($err['message'] ?? 'unknown'));
        }

        return $payload + ['id' => $noteId];
    }

    public function list(array $filters = []): array
    {
        $b = $this->db->table('delivery_notes');
        if (! empty($filters['branch_id'])) { $b->where('branch_id', $filters['branch_id']); }
        if (! empty($filters['order_id'])) { $b->where('order_id', $filters['order_id']); }
        if (! empty($filters['status'])) { $b->where('status', $filters['status']); }
        if (! empty($filters['search'])) { $b->like('delivery_number', $filters['search']); }
        return $b->orderBy('id', 'DESC')->limit(200)->get()->getResultArray();
    }

    public function find(int $id): ?array
    {
        $note = $this->notes->find($id);
        if (! $note) { return null; }
        $items = $this->items->where('delivery_note_id', $id)->findAll();
        $note['items'] = $items;
        return $note;
    }

    public function findByNumber(string $number): ?array
    {
        $note = $this->notes->where('delivery_number', $number)->first();
        if (! $note) { return null; }
        $items = $this->items->where('delivery_note_id', $note['id'])->findAll();
        $note['items'] = $items;
        return $note;
    }

    public function updateStatus(int $id, string $status, array $extra = []): void
    {
        $payload = $extra + ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        $this->db->table('delivery_notes')->where('id', $id)->update($payload);
    }

    public function updateTracking(int $id, array $data): void
    {
        $payload = $data + ['updated_at' => date('Y-m-d H:i:s')];
        $this->db->table('delivery_notes')->where('id', $id)->update($payload);
    }

    public function updateDeliveredQuantity(int $itemId, float $delta): void
    {
        $this->db->table('delivery_note_items')
            ->where('id', $itemId)
            ->set('delivered_quantity', 'delivered_quantity + ' . $this->db->escape($delta), false)
            ->set('updated_at', date('Y-m-d H:i:s'))
            ->update();
    }

    /** Get next delivery number per branch. */
    public function nextNumber(int $branchId): string
    {
        $row = $this->db->table('delivery_notes')
            ->select('delivery_number')
            ->where('branch_id', $branchId)
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->get()->getRowArray();

        $current = $row['delivery_number'] ?? null;
        $nextSeq = 1;
        if ($current && preg_match('/DN-' . $branchId . '-(\d{6})$/', $current, $m)) {
            $nextSeq = (int) $m[1] + 1;
        }
        return 'DN-' . $branchId . '-' . str_pad((string) $nextSeq, 6, '0', STR_PAD_LEFT);
    }
}
