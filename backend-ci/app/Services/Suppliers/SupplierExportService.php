<?php

namespace App\Services\Suppliers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use App\Repositories\Partners\PartnerRepository;
use App\Models\SupplierDebtTransactionModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Export suppliers data to Excel.
 *
 * @agent-service: Supplier Export
 * @agent-pattern: List to Excel export
 */
class SupplierExportService
{
    protected PartnerRepository $repo;
    protected SupplierDebtTransactionModel $debtModel;
    protected BaseConnection $db;

    public function __construct()
    {
        $this->repo = new PartnerRepository();
        $this->debtModel = new SupplierDebtTransactionModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Export suppliers list to Excel.
     */
    public function exportSuppliers(array $filters): string
    {
        $filters['type'] = 'supplier';
        $filters['limit'] = $filters['limit'] ?? 1000;
        $items = $this->repo->findAll($filters);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Nhà cung cấp');

        // Headers
        $headers = [
            'Mã NCC', 'Tên nhà cung cấp', 'Điện thoại', 'Email', 
            'Địa chỉ', 'Mã số thuế', 'Nhóm NCC', 'Nợ hiện tại', 
            'Tổng mua', 'Trạng thái', 'Ngày tạo'
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
            $sheet->setCellValue("A{$rowIndex}", $item['code'] ?? '');
            $sheet->setCellValue("B{$rowIndex}", $item['name'] ?? '');
            $sheet->setCellValue("C{$rowIndex}", $item['phone'] ?? '');
            $sheet->setCellValue("D{$rowIndex}", $item['email'] ?? '');
            $sheet->setCellValue("E{$rowIndex}", $item['address'] ?? '');
            $sheet->setCellValue("F{$rowIndex}", $item['tax_code'] ?? '');
            $sheet->setCellValue("G{$rowIndex}", $item['group'] ?? '');
            $sheet->setCellValue("H{$rowIndex}", $item['current_debt'] ?? 0);
            $sheet->setCellValue("I{$rowIndex}", $item['total_purchase'] ?? 0);
            $sheet->setCellValue("J{$rowIndex}", $item['status'] === 'ACTIVE' ? 'Hoạt động' : 'Ngừng');
            $sheet->setCellValue("K{$rowIndex}", $item['created_at'] ? date('d/m/Y', strtotime($item['created_at'])) : '');
            $rowIndex++;
        }

        // Format number columns
        $lastRow = $rowIndex - 1;
        if ($lastRow >= 2) {
            $sheet->getStyle("H2:I{$lastRow}")->getNumberFormat()
                ->setFormatCode('#,##0');
        }

        // Auto-size columns
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->saveFile($spreadsheet, 'suppliers');
    }

    /**
     * Export supplier receipts (purchase history) to Excel.
     */
    public function exportReceipts(int $supplierId): string
    {
        $orders = $this->db->table('purchase_orders po')
            ->select('po.order_number as code, po.order_date, po.total, po.paid_amount, po.status, 
                      po.notes, u.full_name as creator, b.name as branch')
            ->join('users u', 'u.id = po.created_by', 'left')
            ->join('branches b', 'b.id = po.branch_id', 'left')
            ->groupStart()
                ->where('po.partner_id', $supplierId)
                ->orWhere('po.supplier_id', $supplierId)
            ->groupEnd()
            ->orderBy('po.order_date', 'DESC')
            ->get()
            ->getResultArray();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Lịch sử nhập hàng');

        // Headers
        $headers = ['Mã phiếu', 'Thời gian', 'Người tạo', 'Chi nhánh', 'Tổng tiền', 'Đã trả', 'Còn nợ', 'Trạng thái', 'Ghi chú'];
        foreach ($headers as $idx => $header) {
            $col = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue("{$col}1", $header);
        }
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:I1')->getFont()->getColor()->setRGB('FFFFFF');

        // Data
        $rowIndex = 2;
        foreach ($orders as $row) {
            $total = (float) ($row['total'] ?? 0);
            $paid = (float) ($row['paid_amount'] ?? 0);
            
            $sheet->setCellValue("A{$rowIndex}", $row['code'] ?? '');
            $sheet->setCellValue("B{$rowIndex}", $row['order_date'] ? date('d/m/Y H:i', strtotime($row['order_date'])) : '');
            $sheet->setCellValue("C{$rowIndex}", $row['creator'] ?? '');
            $sheet->setCellValue("D{$rowIndex}", $row['branch'] ?? '');
            $sheet->setCellValue("E{$rowIndex}", $total);
            $sheet->setCellValue("F{$rowIndex}", $paid);
            $sheet->setCellValue("G{$rowIndex}", $total - $paid);
            $sheet->setCellValue("H{$rowIndex}", $this->mapStatus($row['status'] ?? ''));
            $sheet->setCellValue("I{$rowIndex}", $row['notes'] ?? '');
            $rowIndex++;
        }

        // Format
        $lastRow = $rowIndex - 1;
        if ($lastRow >= 2) {
            $sheet->getStyle("E2:G{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->saveFile($spreadsheet, 'supplier_receipts');
    }

    /**
     * Export supplier payables (debt history) to Excel.
     */
    public function exportPayables(int $supplierId): string
    {
        $transactions = $this->debtModel
            ->where('partner_id', $supplierId)
            ->orderBy('transaction_date', 'DESC')
            ->findAll();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Công nợ NCC');

        // Headers
        $headers = ['Mã giao dịch', 'Thời gian', 'Loại', 'Giá trị', 'Nợ trước GD', 'Nợ sau GD', 'Phương thức', 'Ghi chú'];
        foreach ($headers as $idx => $header) {
            $col = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue("{$col}1", $header);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:H1')->getFont()->getColor()->setRGB('FFFFFF');

        // Data
        $rowIndex = 2;
        foreach ($transactions as $row) {
            $type = $row['type'] ?? '';
            $typeLabel = match ($type) {
                'adjust' => 'Điều chỉnh',
                'payment' => 'Thanh toán',
                'discount' => 'Chiết khấu',
                default => 'Nhập hàng',
            };
            
            $value = (float) ($row['amount'] ?? 0);
            if (in_array($type, ['payment', 'discount'])) {
                $value = -$value;
            }

            $sheet->setCellValue("A{$rowIndex}", 'TX-' . $row['id']);
            $sheet->setCellValue("B{$rowIndex}", $row['transaction_date'] ? date('d/m/Y H:i', strtotime($row['transaction_date'])) : '');
            $sheet->setCellValue("C{$rowIndex}", $typeLabel);
            $sheet->setCellValue("D{$rowIndex}", $value);
            $sheet->setCellValue("E{$rowIndex}", (float) ($row['debt_before'] ?? 0));
            $sheet->setCellValue("F{$rowIndex}", (float) ($row['debt_after'] ?? 0));
            $sheet->setCellValue("G{$rowIndex}", $row['payment_method'] ?? '');
            $sheet->setCellValue("H{$rowIndex}", $row['note'] ?? '');
            $rowIndex++;
        }

        // Format
        $lastRow = $rowIndex - 1;
        if ($lastRow >= 2) {
            $sheet->getStyle("D2:F{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->saveFile($spreadsheet, 'supplier_payables');
    }

    private function mapStatus(string $status): string
    {
        return match ($status) {
            'draft' => 'Nháp',
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
