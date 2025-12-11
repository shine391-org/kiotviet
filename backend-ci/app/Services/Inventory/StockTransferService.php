<?php

namespace App\Services\Inventory;

use App\Repositories\Inventory\StockTransferRepository;
use App\Validators\StockTransferValidator;
use RuntimeException;

class StockTransferService
{
    protected StockTransferRepository $repo;
    protected StockTransferValidator $validator;
    protected ?StockLedgerService $ledger;

    public function __construct(
        ?StockTransferRepository $repo = null,
        ?StockTransferValidator $validator = null,
        ?StockLedgerService $ledger = null
    ) {
        $this->repo = $repo ?? new StockTransferRepository();
        $this->validator = $validator ?? new StockTransferValidator();
        $this->ledger = $ledger;
    }

    public function list(array $filters): array
    {
        $validated = $this->validator->validateListFilters($filters);
        $items = $this->repo->findAll($validated);
        $total = $this->repo->count($validated);
        $summary = $this->repo->getSummary($validated);

        $limit = (int) ($validated['limit'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);

        return [
            'success' => true,
            'data' => $this->transformList($items),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => (int) ceil($total / $limit),
            ],
            'summary' => $summary,
        ];
    }

    public function show(string $code): array
    {
        $transfer = $this->repo->findByCode($code);
        if (!$transfer) {
            throw new RuntimeException('Không tìm thấy phiếu chuyển hàng');
        }

        $items = $this->repo->findItems((int) $transfer['id']);
        $transfer['items'] = $items;

        return [
            'success' => true,
            'transfer' => $this->transformDetail($transfer),
        ];
    }

    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        
        $code = $this->repo->nextCode();
        $now = date('Y-m-d H:i:s');

        $items = $data['items'] ?? [];
        $totalItems = count($items);
        $quantitySent = array_sum(array_column($items, 'quantity_sent'));
        $valueSent = array_sum(array_map(fn($i) => ($i['quantity_sent'] ?? 0) * ($i['unit_price'] ?? 0), $items));

        $transferData = [
            'code' => $code,
            'status' => 'draft',
            'from_branch_id' => $data['from_branch_id'],
            'to_branch_id' => $data['to_branch_id'],
            'transfer_date' => $data['transfer_date'] ?? $now,
            'notes' => $data['notes'] ?? null,
            'total_items' => $totalItems,
            'quantity_sent' => $quantitySent,
            'value_sent' => $valueSent,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => $now,
        ];

        $itemData = array_map(fn($item) => [
            'product_id' => $item['product_id'],
            'variant_id' => $item['variant_id'] ?? null,
            'product_code' => $item['product_code'] ?? null,
            'product_name' => $item['product_name'] ?? null,
            'unit' => $item['unit'] ?? null,
            'quantity_sent' => $item['quantity_sent'] ?? 0,
            'unit_price' => $item['unit_price'] ?? 0,
            'total_price' => ($item['quantity_sent'] ?? 0) * ($item['unit_price'] ?? 0),
        ], $items);

        $created = $this->repo->create($transferData, $itemData);

        return ['success' => true, 'data' => $created];
    }

    public function update(int $id, array $input): array
    {
        $transfer = $this->repo->findById($id);
        if (!$transfer) {
            throw new RuntimeException('Không tìm thấy phiếu chuyển hàng');
        }

        if ($transfer['status'] !== 'draft') {
            throw new RuntimeException('Chỉ có thể cập nhật phiếu ở trạng thái nháp');
        }

        $data = $this->validator->validateUpdate($input);

        $updateData = [];
        if (isset($data['to_branch_id'])) {
            $updateData['to_branch_id'] = $data['to_branch_id'];
        }
        if (isset($data['notes'])) {
            $updateData['notes'] = $data['notes'];
        }
        if (isset($data['transfer_date'])) {
            $updateData['transfer_date'] = $data['transfer_date'];
        }

        if (!empty($data['items'])) {
            $items = $data['items'];
            $updateData['total_items'] = count($items);
            $updateData['quantity_sent'] = array_sum(array_column($items, 'quantity_sent'));
            $updateData['value_sent'] = array_sum(array_map(fn($i) => ($i['quantity_sent'] ?? 0) * ($i['unit_price'] ?? 0), $items));

            $itemData = array_map(fn($item) => [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'product_code' => $item['product_code'] ?? null,
                'product_name' => $item['product_name'] ?? null,
                'unit' => $item['unit'] ?? null,
                'quantity_sent' => $item['quantity_sent'] ?? 0,
                'unit_price' => $item['unit_price'] ?? 0,
                'total_price' => ($item['quantity_sent'] ?? 0) * ($item['unit_price'] ?? 0),
            ], $items);

            $this->repo->updateItems($id, $itemData);
        }

        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $this->repo->update($id, $updateData);
        }

        return ['success' => true, 'data' => $this->repo->findById($id)];
    }

    public function submit(int $id): array
    {
        $transfer = $this->repo->findById($id);
        if (!$transfer) {
            throw new RuntimeException('Không tìm thấy phiếu chuyển hàng');
        }

        if ($transfer['status'] === 'in_transit') {
            return ['success' => true, 'data' => $transfer];
        }

        if ($transfer['status'] !== 'draft') {
            throw new RuntimeException('Chỉ có thể gửi phiếu ở trạng thái nháp');
        }

        $updated = $this->repo->update($id, [
            'status' => 'in_transit',
            'transfer_date' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'data' => $updated];
    }

    public function submitByCode(string $code): array
    {
        $transfer = $this->repo->findByCode($code);
        if (!$transfer) {
            throw new RuntimeException('Không tìm thấy phiếu chuyển hàng');
        }
        return $this->submit((int) $transfer['id']);
    }

    public function receive(int $id, array $input): array
    {
        $transfer = $this->repo->findById($id);
        if (!$transfer) {
            throw new RuntimeException('Không tìm thấy phiếu chuyển hàng');
        }

        if ($transfer['status'] === 'received') {
            return ['success' => true, 'data' => $transfer];
        }

        if (!in_array($transfer['status'], ['draft', 'in_transit'])) {
            throw new RuntimeException('Không thể nhận hàng cho phiếu này');
        }

        $receivedItems = $input['items'] ?? [];
        $quantityReceived = 0;
        $valueReceived = 0;

        // Get all transfer items
        $items = $this->repo->findItems($id);
        
        // Collect item updates for batch processing
        $itemUpdates = [];
        
        foreach ($items as $item) {
            // Omitted items are treated as 0 received
            $receivedQty = isset($receivedItems[$item['id']]) ? (float)$receivedItems[$item['id']] : 0;
            $quantityReceived += $receivedQty;
            $valueReceived += $receivedQty * (float)$item['unit_price'];
            
            // Collect update for this item
            $itemUpdates[] = [
                'id' => $item['id'],
                'quantity_received' => $receivedQty,
            ];
        }
        
        // Batch update all item received quantities
        foreach ($itemUpdates as $update) {
            $this->repo->updateItem($update['id'], ['quantity_received' => $update['quantity_received']]);
        }

        $updated = $this->repo->update($id, [
            'status' => 'received',
            'receive_date' => date('Y-m-d H:i:s'),
            'quantity_received' => $quantityReceived,
            'value_received' => $valueReceived,
            'receiving_notes' => $input['receiving_notes'] ?? null,
            'received_by' => $input['received_by'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'data' => $updated];
    }

    public function cancel(int $id): array
    {
        $transfer = $this->repo->findById($id);
        if (!$transfer) {
            throw new RuntimeException('Không tìm thấy phiếu chuyển hàng');
        }

        if ($transfer['status'] === 'cancelled') {
            return ['success' => true, 'data' => $transfer];
        }

        if ($transfer['status'] === 'received') {
            throw new RuntimeException('Không thể hủy phiếu đã nhận hàng');
        }

        $updated = $this->repo->update($id, [
            'status' => 'cancelled',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'data' => $updated];
    }

    public function duplicate(string $code): array
    {
        $original = $this->repo->findByCode($code);
        if (!$original) {
            throw new RuntimeException('Không tìm thấy phiếu chuyển hàng');
        }

        $items = $this->repo->findItems((int) $original['id']);
        $newCode = $this->repo->nextCode();
        $now = date('Y-m-d H:i:s');

        $newData = [
            'code' => $newCode,
            'status' => 'draft',
            'from_branch_id' => $original['from_branch_id'],
            'to_branch_id' => $original['to_branch_id'],
            'transfer_date' => $now,
            'notes' => $original['notes'],
            'total_items' => $original['total_items'],
            'quantity_sent' => $original['quantity_sent'],
            'value_sent' => $original['value_sent'],
            'created_by' => $original['created_by'],
            'created_at' => $now,
        ];

        $newItems = array_map(fn($item) => [
            'product_id' => $item['product_id'],
            'variant_id' => $item['variant_id'],
            'product_code' => $item['product_code'],
            'product_name' => $item['product_name'],
            'unit' => $item['unit'],
            'quantity_sent' => $item['quantity_sent'],
            'unit_price' => $item['unit_price'],
            'total_price' => $item['total_price'],
        ], $items);

        $created = $this->repo->create($newData, $newItems);

        return ['success' => true, 'data' => $created];
    }

    public function saveNotes(string $code, string $notes): array
    {
        $transfer = $this->repo->findByCode($code);
        if (!$transfer) {
            throw new RuntimeException('Không tìm thấy phiếu chuyển hàng');
        }

        $updated = $this->repo->update((int) $transfer['id'], [
            'receiving_notes' => $notes,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'data' => $updated];
    }

    protected function transformList(array $items): array
    {
        return array_map(fn($item) => [
            'id' => $item['code'],
            'code' => $item['code'],
            'creatorName' => $item['creator_name'] ?? 'N/A',
            'receiverName' => $item['receiver_name'] ?? '',
            'transferDate' => $item['transfer_date'],
            'receiveDate' => $item['receive_date'],
            'createdAt' => $item['created_at'],
            'fromBranch' => $item['from_branch_name'] ?? '',
            'toBranch' => $item['to_branch_name'] ?? '',
            'quantitySent' => (float) $item['quantity_sent'],
            'valueSent' => (float) $item['value_sent'],
            'quantityReceived' => (float) $item['quantity_received'],
            'valueReceived' => (float) $item['value_received'],
            'totalItems' => (int) $item['total_items'],
            'notes' => $item['notes'] ?? '',
            'status' => $item['status'],
        ], $items);
    }

    protected function transformDetail(array $transfer): array
    {
        return [
            'id' => $transfer['code'],
            'code' => $transfer['code'],
            'status' => $transfer['status'],
            'creatorName' => $transfer['creator_name'] ?? 'N/A',
            'fromBranch' => $transfer['from_branch_name'] ?? '',
            'toBranch' => $transfer['to_branch_name'] ?? '',
            'transferDate' => $transfer['transfer_date'],
            'receiveDate' => $transfer['receive_date'],
            'receivingNotes' => $transfer['receiving_notes'] ?? '',
            'notes' => $transfer['notes'] ?? '',
            'items' => array_map(fn($item) => [
                'productCode' => $item['product_code'],
                'productName' => $item['product_name'],
                'quantitySent' => (float) $item['quantity_sent'],
                'quantityReceived' => (float) $item['quantity_received'],
                'unitPrice' => (float) $item['unit_price'],
                'totalPrice' => (float) $item['total_price'],
            ], $transfer['items'] ?? []),
            'summary' => [
                'totalItems' => (int) $transfer['total_items'],
                'totalQtySent' => (float) $transfer['quantity_sent'],
                'totalValueSent' => (float) $transfer['value_sent'],
                'totalQtyReceived' => (float) $transfer['quantity_received'],
                'totalValueReceived' => (float) $transfer['value_received'],
            ],
        ];
    }
}
