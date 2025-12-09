// src/constants/purchases.js
import { format, startOfMonth } from 'date-fns';

export const startOfMonthIso = () => format(startOfMonth(new Date()), 'yyyy-MM-dd');

export const PURCHASE_STATUSES = [
    { value: 'draft', label: 'Phiếu tạm', color: 'blue' },
    { value: 'pending', label: 'Chờ xác nhận', color: 'gold' },
    { value: 'confirmed', label: 'Đã xác nhận', color: 'cyan' },
    { value: 'in_transit', label: 'Đang vận chuyển', color: 'orange' },
    { value: 'received', label: 'Đã nhận', color: 'lime' },
    { value: 'completed', label: 'Đã nhập hàng', color: 'green' },
    { value: 'cancelled', label: 'Đã hủy', color: 'red' },
];

export const PAYMENT_STATUSES = [
    { value: 'unpaid', label: 'Chưa thanh toán', color: 'red' },
    { value: 'partial', label: 'Thanh toán một phần', color: 'orange' },
    { value: 'paid', label: 'Đã thanh toán', color: 'green' },
];

// Full list of available columns - matching KiotViet design
export const ALL_PURCHASE_COLUMNS = [
    { key: 'order_number', label: 'Mã nhập hàng' },
    { key: 'return_code', label: 'Mã trả hàng nhập' },
    { key: 'order_date', label: 'Thời gian' },
    { key: 'created_at', label: 'Thời gian tạo' },
    { key: 'updated_at', label: 'Ngày cập nhật' },
    { key: 'supplier_code', label: 'Mã NCC' },
    { key: 'supplier_name', label: 'Nhà cung cấp' },
    { key: 'branch_name', label: 'Chi nhánh' },
    { key: 'receiver_name', label: 'Người nhập' },
    { key: 'creator_name', label: 'Người tạo' },
    { key: 'total_quantity', label: 'Tổng số lượng' },
    { key: 'lost_quantity', label: 'Số lượng mất hàng' },
    { key: 'total', label: 'Tổng tiền hàng' },
    { key: 'discount', label: 'Giảm giá' },
    { key: 'debt', label: 'Cần trả NCC' },
    { key: 'discount_paid', label: 'Chiết khấu thanh toán' },
    { key: 'paid_amount', label: 'Tiền đã trả NCC' },
    { key: 'balance', label: 'Tiền chi' },
    { key: 'notes', label: 'Ghi chú' },
    { key: 'status', label: 'Trạng thái' },
];

// Default visible columns
export const DEFAULT_PURCHASE_COLUMNS = [
    'order_number',
    'order_date',
    'supplier_code',
    'supplier_name',
    'branch_name',
    'total',
    'paid_amount',
    'debt',
    'receiver_name',
    'status',
];

export const formatPurchaseDate = (dateString) => {
    if (!dateString) return '—';
    try {
        return format(new Date(dateString), 'dd/MM/yyyy HH:mm');
    } catch {
        return dateString;
    }
};

