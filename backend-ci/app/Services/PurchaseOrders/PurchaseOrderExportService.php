<?php

namespace App\Services\PurchaseOrders;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use CodeIgniter\Database\BaseConnection;

/**
 * Export purchase orders data to Excel.
 *
 * @agent-service: Purchase Order Export
 * @agent-pattern: List to Excel export
 */
class PurchaseOrderExportService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Export purchase orders list to Excel.
     */
    public function exportOrders(array $filters): string
    {
        $filters['limit'] = $filters['limit'] ?? 5000;
        
        $builder = $this->db->table('purchase_orders po')
            ->select('po.*, p.name as supplier_name, p.code as supplier_code, 
                      u.full_name as creator_name, b.name as branch_name')
            ->join('partners p', 'p.id = po.partner_id', 'left')
            ->join('users u', 'u.id = po.created_by', 'left')
            ->join('branches b', 'b.id = po.branch_id', 'left');

        // Apply filters
        if (!empty($filters['branch_id'])) {
            $builder->where('po.branch_id', $filters['branch_id']);
        }
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $builder->whereIn('po.status', $filters['status']);
            } else {
                $builder->where('po.status', $filters['status']);
            }
        }
        if (!empty($filters['supplier_id'])) {
            $builder->where('po.partner_id', $filters['supplier_id']);
        }
        if (!empty($filters['date_from'])) {
            $builder->where('po.order_date >=', $filters['date_from'] . ' 00:00:00');
        }
        if (!empty($filters['date_to'])) {
            $builder->where('po.order_date <=', $filters['date_to'] . ' 23:59:59');
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('po.order_number', $search)
                ->orLike('po.po_number', $search)
                ->orLike('p.name', $search)
                ->groupEnd();
        }

        $builder->orderBy('po.order_date', 'DESC')
            ->limit((int) $filters['limit']);

        $items = $builder->get()->getResultArray();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Phiếu nhập hàng');

        // Headers
        $headers = [
            'Mã nhập hàng', 'Thời gian', 'Mã NCC', 'Nhà cung cấp', 
            'Chi nhánh', 'Tổng tiền', 'Đã trả NCC', 'Cần trả NCC',
            'Người tạo', 'Trạng thái', 'Ghi chú'
        ];
        foreach ($headers as $idx => $header) {
            $col = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue("{$col}1", $header);
        }
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('A1:K1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:K1')->getFont()->getColor()->setRGB('FFFFFF');

        // Data rows
        $rowIndex = 2;
        foreach ($items as $item) {
            $total = (float) ($item['total'] ?? 0);
            $paid = (float) ($item['paid_amount'] ?? 0);
            
            $sheet->setCellValue("A{$rowIndex}", $item['order_number'] ?? $item['po_number'] ?? '');
            $sheet->setCellValue("B{$rowIndex}", $item['order_date'] ? date('d/m/Y H:i', strtotime($item['order_date'])) : '');
            $sheet->setCellValue("C{$rowIndex}", $item['supplier_code'] ?? '');
            $sheet->setCellValue("D{$rowIndex}", $item['supplier_name'] ?? '');
            $sheet->setCellValue("E{$rowIndex}", $item['branch_name'] ?? '');
            $sheet->setCellValue("F{$rowIndex}", $total);
            $sheet->setCellValue("G{$rowIndex}", $paid);
            $sheet->setCellValue("H{$rowIndex}", $total - $paid);
            $sheet->setCellValue("I{$rowIndex}", $item['creator_name'] ?? '');
            $sheet->setCellValue("J{$rowIndex}", $this->mapStatus($item['status'] ?? ''));
            $sheet->setCellValue("K{$rowIndex}", $item['notes'] ?? '');
            $rowIndex++;
        }

        // Format number columns
        $lastRow = $rowIndex - 1;
        if ($lastRow >= 2) {
            $sheet->getStyle("F2:H{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode('#,##0');
        }

        // Auto-size columns
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add summary row
        $summaryRow = $lastRow + 2;
        $sheet->setCellValue("E{$summaryRow}", 'Tổng cộng:');
        $sheet->setCellValue("F{$summaryRow}", "=SUM(F2:F{$lastRow})");
        $sheet->setCellValue("G{$summaryRow}", "=SUM(G2:G{$lastRow})");
        $sheet->setCellValue("H{$summaryRow}", "=SUM(H2:H{$lastRow})");
        $sheet->getStyle("E{$summaryRow}:H{$summaryRow}")->getFont()->setBold(true);
        $sheet->getStyle("F{$summaryRow}:H{$summaryRow}")
            ->getNumberFormat()
            ->setFormatCode('#,##0');

        return $this->saveFile($spreadsheet, 'purchase_orders');
    }

    private function mapStatus(string $status): string
    {
        return match ($status) {
            'draft' => 'Phiếu tạm',
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'in_transit' => 'Đang vận chuyển',
            'received' => 'Đã nhận hàng',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            default => $status,
        };
    }

    private function saveFile(Spreadsheet $spreadsheet, string $prefix): string
    {
        $dir = WRITEPATH . 'exports';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $filepath = $dir . "/{$prefix}_" . date('Ymd_His') . '.xlsx';
        (new Xlsx($spreadsheet))->save($filepath);
        return $filepath;
    }
}
