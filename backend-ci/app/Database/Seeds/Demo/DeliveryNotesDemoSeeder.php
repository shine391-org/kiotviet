<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;
use DateTimeImmutable;

/**
 * DeliveryNotesDemoSeeder - Demo delivery notes for testing delivery workflows
 * 
 * @agent-seeder: Demo delivery notes
 * @agent-pattern: Development demo data
 * @agent-reusable: HIGH
 * 
 * Depends on: OrdersDemoSeeder
 */
class DeliveryNotesDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! $this->db->tableExists('delivery_notes')) {
            return;
        }

        echo "   → Demo delivery notes...\n";

        $this->cleanupExisting();

        $orders = DemoOrderHelper::orders($this->db);
        $orders = array_filter($orders, static fn ($order) => ($order['status'] ?? '') !== 'cancelled');
        if (empty($orders)) {
            echo "      ⚠️  No demo orders found for delivery notes\n";
            return;
        }

        $orderIds = array_map(static fn ($row) => (int) $row['id'], array_values($orders));
        $itemsByOrder = DemoOrderHelper::orderItemsByOrder($this->db, $orderIds);

        [$notes, $items] = $this->buildPayload($orders, $itemsByOrder);

        $noteIdMap = [];
        foreach ($notes as $note) {
            $this->db->table('delivery_notes')->insert($note);
            $noteIdMap[$note['delivery_number']] = (int) $this->db->insertID();
        }

        if (! empty($items) && $this->db->tableExists('delivery_note_items')) {
            foreach ($items as &$item) {
                $item['delivery_note_id'] = $noteIdMap[$item['delivery_number']] ?? null;
                unset($item['delivery_number']);
            }
            unset($item);
            $this->db->table('delivery_note_items')->insertBatch($items);
        }

        echo "      ✓ Created " . count($notes) . " demo delivery notes\n";
    }

    private function buildPayload(array $orders, array $itemsByOrder): array
    {
        $notes = [];
        $items = [];
        $counter = 1;

        foreach ($orders as $order) {
            $deliveryNumber = sprintf('DN-DEMO-%03d', $counter++);
            $orderDate = $order['order_date'] ?? date('Y-m-d');
            $date = new DateTimeImmutable($orderDate);
            $deliveryDate = $date->modify('+1 day')->format('Y-m-d');
            $status = $this->resolveStatus($order['status'] ?? 'draft', $counter);
            $tracking = 'TRK-' . substr($deliveryNumber, -3);
            $timestamp = Time::now()->toDateTimeString();

            $notes[] = [
                'delivery_number' => $deliveryNumber,
                'order_id' => (int) $order['id'],
                'customer_id' => $order['customer_id'] ?? null,
                'branch_id' => $order['branch_id'] ?? null,
                'delivery_date' => $deliveryDate,
                'expected_delivery_date' => $deliveryDate,
                'shipping_address' => $order['shipping_address'] ?? null,
                'status' => $status,
                'carrier' => 'Demo Carrier',
                'tracking_number' => $tracking,
                'notes' => 'Demo delivery note from ' . ($order['order_number'] ?? 'order'),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            $orderItems = $itemsByOrder[(int) $order['id']] ?? [];
            foreach ($orderItems as $orderItem) {
                $orderedQty = (float) ($orderItem['quantity'] ?? 0);
                $deliveredQty = $this->deliveredQuantity($status, $orderedQty);
                $items[] = [
                    'delivery_number' => $deliveryNumber,
                    'order_item_id' => (int) $orderItem['id'],
                    'product_id' => $orderItem['product_id'],
                    'variant_id' => $orderItem['variant_id'],
                    'batch_id' => null,
                    'serial_number' => null,
                    'quantity' => $orderedQty,
                    'delivered_quantity' => $deliveredQty,
                    'notes' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        return [$notes, $items];
    }

    private function deliveredQuantity(string $status, float $qty): float
    {
        if ($status === 'delivered') {
            return $qty;
        }
        if ($status === 'shipped') {
            return round($qty / 2, 2);
        }
        return 0.0;
    }

    private function resolveStatus(string $orderStatus, int $counter): string
    {
        if ($orderStatus === 'completed') {
            return 'delivered';
        }
        if ($orderStatus === 'shipping') {
            return $counter % 2 === 0 ? 'delivered' : 'shipped';
        }
        if ($orderStatus === 'processing') {
            return 'confirmed';
        }
        return 'draft';
    }

    private function cleanupExisting(): void
    {
        $existingNotes = [];
        if ($this->db->tableExists('delivery_notes')) {
            $existingNotes = $this->db->table('delivery_notes')
                ->select('id')
                ->like('delivery_number', 'DN-DEMO-', 'after')
                ->get()
                ->getResultArray();
        }

        if (! empty($existingNotes) && $this->db->tableExists('delivery_note_items')) {
            $ids = array_map(static fn ($row) => (int) $row['id'], $existingNotes);
            $this->db->table('delivery_note_items')->whereIn('delivery_note_id', $ids)->delete();
        }

        if (! empty($existingNotes)) {
            $ids = array_map(static fn ($row) => (int) $row['id'], $existingNotes);
            $this->db->table('delivery_notes')->whereIn('id', $ids)->delete();
        }
    }
}
