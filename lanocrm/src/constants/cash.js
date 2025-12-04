// src/constants/cash.js
// Cash module shared constants & helpers

import { format } from 'date-fns';

export const CASH_TYPES = [
  { value: 'RECEIPT', label: 'Phiếu thu', pill: 'success' },
  { value: 'PAYMENT', label: 'Phiếu chi', pill: 'error' },
];

export const PAYMENT_METHODS = [
  { value: 'cash', label: 'Tiền mặt' },
  { value: 'bank', label: 'Ngân hàng' },
  { value: 'bank_transfer', label: 'Chuyển khoản' },
  { value: 'ewallet', label: 'Ví điện tử' },
];

export const STATUS_OPTIONS = [
  { value: 'approved', label: 'Đã thanh toán' },
  { value: 'pending', label: 'Chờ xử lý' },
  { value: 'cancelled', label: 'Đã hủy' },
];

export const REFERENCE_TYPES = [
  { value: 'order', label: 'Đơn hàng' },
  { value: 'order_payment', label: 'Thanh toán đơn hàng' },
  { value: 'purchase_order', label: 'Nhập hàng' },
  { value: 'return_order', label: 'Trả hàng' },
  { value: 'shipping_settlement', label: 'Quyết toán giao hàng' },
  { value: 'expense', label: 'Chi phí' },
  { value: 'manual', label: 'Thủ công' },
];

export const RECEIPT_CATEGORIES = [
  { value: 'sales', label: 'Thu từ bán hàng' },
  { value: 'refund', label: 'Hoàn tiền / thu hồi' },
  { value: 'deposit', label: 'Nhận đặt cọc' },
  { value: 'other_income', label: 'Thu khác' },
  { value: 'shipping_cod', label: 'Thu COD giao hàng' },
];

export const PAYMENT_CATEGORIES = [
  { value: 'purchase', label: 'Chi mua hàng' },
  { value: 'salary', label: 'Chi lương' },
  { value: 'expense', label: 'Chi phí vận hành' },
  { value: 'withdrawal', label: 'Rút tiền gửi NH' },
  { value: 'other_expense', label: 'Chi khác' },
  { value: 'refund', label: 'Hoàn tiền cho khách' },
  { value: 'shipping_fee', label: 'Cước vận chuyển' },
];

export const DEFAULT_PAGE_SIZE = 20;
// Phải <= giới hạn validator (200) để tránh 400 Bad Request
export const SUMMARY_SAMPLE_LIMIT = 200; // số bản ghi tối đa để tính tổng front-end

export const getCategoriesByType = (type) => {
  if (type === 'RECEIPT') return RECEIPT_CATEGORIES;
  if (type === 'PAYMENT') return PAYMENT_CATEGORIES;
  return [...RECEIPT_CATEGORIES, ...PAYMENT_CATEGORIES];
};

export const formatTransactionCode = (txn) => {
  if (!txn) return '—';
  if (txn.reference_code) return txn.reference_code;
  return `CT-${String(txn.id || 0).padStart(5, '0')}`;
};

export const today = () => format(new Date(), 'yyyy-MM-dd');
