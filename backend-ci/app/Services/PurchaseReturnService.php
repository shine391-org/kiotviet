<?php

namespace App\Services;

use App\Repositories\PurchaseReturns\PurchaseReturnRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * PurchaseReturnService - Business logic for purchase returns (Trả hàng nhập)
 * @agent-layer: backend-service
 */
class PurchaseReturnService
{
    protected PurchaseReturnRepository $repository;

    public function __construct(?PurchaseReturnRepository $repository = null)
    {
        $this->repository = $repository ?? new PurchaseReturnRepository();
    }

    public function list(array $filters = []): array
    {
        return $this->repository->list($filters);
    }

    public function findById(int $id): ?array
    {
        return $this->repository->findById($id);
    }

    public function create(array $data, array $items, int $userId): array
    {
        // Validate items is non-empty
        if (empty($items) || !is_array($items)) {
            throw new \InvalidArgumentException('Cannot create purchase return with no items');
        }

        $data['created_by'] = $userId;
        $data['status'] = $data['status'] ?? 'draft';

        // Calculate totals
        $totalQuantity = 0;
        $totalAmount = 0;
        foreach ($items as $item) {
            $totalQuantity += (int)($item['quantity'] ?? 0);
            $totalAmount += (float)($item['amount'] ?? 0);
        }
        $data['total_quantity'] = $totalQuantity;
        $data['total_amount'] = $totalAmount;
        $data['ncc_can_tra'] = $totalAmount - (float)($data['discount'] ?? 0);

        return $this->repository->create($data, $items);
    }

    public function update(int $id, array $data, array $items = []): ?array
    {
        // Recalculate totals if items provided
        if (!empty($items)) {
            $totalQuantity = 0;
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalQuantity += (int)($item['quantity'] ?? 0);
                $totalAmount += (float)($item['amount'] ?? 0);
            }
            $data['total_quantity'] = $totalQuantity;
            $data['total_amount'] = $totalAmount;
            $data['ncc_can_tra'] = $totalAmount - (float)($data['discount'] ?? 0);
        }

        return $this->repository->update($id, $data, $items);
    }

    public function updateStatus(int $id, string $status, ?int $returnedBy = null): bool
    {
        $data = ['status' => $status];
        if ($status === 'returned' && $returnedBy) {
            $data['returned_by'] = $returnedBy;
            $data['return_date'] = date('Y-m-d H:i:s');
        }
        return $this->repository->updateStatus($id, $status, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Export purchase returns to Excel with chunked processing
     */
    public function export(array $filters = []): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Trả hàng nhập');

        // Headers
        $headers = [
            'Mã trả hàng nhập',
            'Mã nhập hàng',
            'Thời gian',
            'Nhà cung cấp',
            'Chi nhánh',
            'Tổng tiền hàng',
            'Giảm giá',
            'NCC cần trả',
            'NCC đã trả',
            'Trạng thái',
            'Người tạo',
            'Người trả',
            'Ghi chú',
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        // Style header row
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);
        $sheet->getStyle('A1:M1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E8E8E8');

        // Process data in chunks to avoid memory exhaustion
        $chunkSize = 500;
        $page = 1;
        $row = 2;
        $hasMore = true;

        while ($hasMore) {
            $filters['page'] = $page;
            $filters['limit'] = $chunkSize;
            $result = $this->repository->list($filters);
            $data = $result['data'];

            if (empty($data)) {
                $hasMore = false;
                break;
            }

            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, $item['return_number']);
                $sheet->setCellValue('B' . $row, $item['purchase_order_number']);
                $sheet->setCellValue('C' . $row, $item['return_date']);
                $sheet->setCellValue('D' . $row, $item['supplier_name']);
                $sheet->setCellValue('E' . $row, $item['branch_name']);
                $sheet->setCellValue('F' . $row, $item['total_amount']);
                $sheet->setCellValue('G' . $row, $item['discount']);
                $sheet->setCellValue('H' . $row, $item['ncc_can_tra']);
                $sheet->setCellValue('I' . $row, $item['ncc_da_tra']);
                $sheet->setCellValue('J' . $row, $this->getStatusLabel($item['status']));
                $sheet->setCellValue('K' . $row, $item['creator_name']);
                $sheet->setCellValue('L' . $row, $item['returner_name']);
                $sheet->setCellValue('M' . $row, $item['notes']);
                $row++;
            }

            // Check if we have more pages
            $hasMore = count($data) === $chunkSize;
            $page++;

            // Free memory after each chunk
            unset($data);
        }

        $filename = WRITEPATH . 'exports/purchase_returns_' . date('Ymd_His') . '.xlsx';
        
        // Ensure directory exists with error handling
        $exportsDir = WRITEPATH . 'exports';
        if (!is_dir($exportsDir)) {
            if (!mkdir($exportsDir, 0755, true) && !is_dir($exportsDir)) {
                throw new \RuntimeException('Failed to create exports directory: ' . $exportsDir);
            }
        }

        // Write file with error handling
        try {
            $writer = new Xlsx($spreadsheet);
            $writer->save($filename);
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'Failed to write export file: ' . $e->getMessage(),
                0,
                $e
            );
        } finally {
            // Free spreadsheet memory
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }

        return $filename;
    }

    private function getStatusLabel(string $status): string
    {
        $labels = [
            'draft' => 'Phiếu tạm',
            'returned' => 'Đã trả hàng',
            'cancelled' => 'Đã hủy',
        ];
        return $labels[$status] ?? $status;
    }
}
