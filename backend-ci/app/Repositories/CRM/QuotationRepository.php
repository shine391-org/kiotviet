<?php

namespace App\Repositories\CRM;

use App\Models\QuotationItemModel;
use App\Models\QuotationModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Quotations
 * @agent-pattern: Repository with items
 * @agent-reusable: MEDIUM
 */
class QuotationRepository
{
    protected QuotationModel $quotes;
    protected QuotationItemModel $items;
    protected BaseConnection $db;

    public function __construct(?QuotationModel $quotes = null, ?QuotationItemModel $items = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->quotes = $quotes ?? new QuotationModel();
        $this->items = $items ?? new QuotationItemModel();
    }

    public function create(array $quote, array $items): array
    {
        $now = $this->now();
        $payload = $quote + ['created_at' => $now, 'updated_at' => $now];
        $this->db->transStart();
        $this->quotes->insert($payload);
        $id = (int) $this->quotes->getInsertID();
        $rows = [];
        foreach ($items as $item) {
            $rows[] = $item + [
                'quotation_id' => $id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows) {
            $this->items->insertBatch($rows);
        }
        $this->db->transComplete();
        return $this->findById($id) ?? ($payload + ['id' => $id, 'items' => $rows]);
    }

    public function findById(int $id): ?array
    {
        $quote = $this->quotes->find($id);
        if (! $quote) { return null; }
        $items = $this->items->where('quotation_id', $id)->get()->getResultArray();
        $quote = $this->hydrate($quote);
        $quote['items'] = array_map(fn ($row) => $this->hydrateItem($row), $items);
        return $quote;
    }

    public function updateStatus(int $id, string $status): bool
    {
        return (bool) $this->quotes->update($id, ['status' => $status, 'updated_at' => $this->now()]);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['opportunity_id'] = isset($row['opportunity_id']) ? (int) $row['opportunity_id'] : null;
        $row['customer_id'] = isset($row['customer_id']) ? (int) $row['customer_id'] : null;
        $row['lead_id'] = isset($row['lead_id']) ? (int) $row['lead_id'] : null;
        $row['subtotal'] = isset($row['subtotal']) ? (float) $row['subtotal'] : 0;
        $row['discount_total'] = isset($row['discount_total']) ? (float) $row['discount_total'] : 0;
        $row['tax_total'] = isset($row['tax_total']) ? (float) $row['tax_total'] : 0;
        $row['total'] = isset($row['total']) ? (float) $row['total'] : 0;
        return $row;
    }

    private function hydrateItem(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['quotation_id'] = isset($row['quotation_id']) ? (int) $row['quotation_id'] : null;
        $row['product_id'] = isset($row['product_id']) ? (int) $row['product_id'] : null;
        $row['quantity'] = isset($row['quantity']) ? (float) $row['quantity'] : 0;
        $row['price'] = isset($row['price']) ? (float) $row['price'] : 0;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
