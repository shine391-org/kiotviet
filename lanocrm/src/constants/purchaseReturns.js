// src/constants/purchaseReturns.js
import { format, startOfMonth } from 'date-fns';

export const startOfMonthIso = () => format(startOfMonth(new Date()), 'yyyy-MM-dd');

export const PURCHASE_RETURN_STATUSES = [
    { value: 'draft', label: 'Phiếu tạm', color: 'blue' },
    { value: 'returned', label: 'Đã trả hàng', color: 'green' },
    { value: 'cancelled', label: 'Đã hủy', color: 'red' },
];

// Full list of available columns - matching KiotViet design
export const ALL_PURCHASE_RETURN_COLUMNS = [
    { key: 'return_number', label: 'Mã trả hàng nhập' },
    { key: 'purchase_order_number', label: 'Mã nhập hàng' },
    { key: 'return_date', label: 'Thời gian' },
    { key: 'created_at', label: 'Thời gian tạo' },
    { key: 'supplier_name', label: 'Nhà cung cấp' },
    { key: 'branch_name', label: 'Chi nhánh' },
    { key: 'returned_by', label: 'Người trả' },
    { key: 'created_by', label: 'Người tạo' },
    { key: 'total_quantity', label: 'Số lượng mặt hàng' },
    { key: 'total_amount', label: 'Tổng tiền hàng' },
    { key: 'discount', label: 'Giảm giá' },
    { key: 'ncc_can_tra', label: 'NCC cần trả' },
    { key: 'ncc_da_tra', label: 'NCC đã trả' },
    { key: 'notes', label: 'Ghi chú' },
    { key: 'status', label: 'Trạng thái' },
];

// Default visible columns
export const DEFAULT_PURCHASE_RETURN_COLUMNS = [
    'return_number',
    'return_date',
    'supplier_name',
    'total_amount',
    'discount',
    'ncc_can_tra',
    'ncc_da_tra',
    'status',
];

export const formatPurchaseReturnDate = (dateString) => {
    if (!dateString) return '—';
    try {
        return format(new Date(dateString), 'dd/MM/yyyy HH:mm');
    } catch {
        return dateString;
    }
};
