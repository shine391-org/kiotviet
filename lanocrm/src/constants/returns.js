import { format, startOfMonth } from 'date-fns';

export const RETURN_TYPES = [
  { value: 'invoice', label: 'Theo hóa đơn' },
  { value: 'quick', label: 'Trả nhanh' },
  { value: 'exchange', label: 'Chuyển hoàn' },
];

export const RETURN_STATUSES = [
  { value: 'completed', label: 'Đã trả', color: 'green' },
  { value: 'processing', label: 'Đang xử lý', color: 'blue' },
  { value: 'cancelled', label: 'Đã hủy', color: 'red' },
];

export const DEFAULT_RETURN_COLUMNS = [
  'return_code',
  'invoice_code',
  'shipping_code',
  'seller_name',
  'return_time',
  'created_at',
  'customer_name',
  'branch_name',
  'status',
];

export const formatReturnDate = (value) => {
  if (!value) return '—';
  try {
    return format(new Date(value), 'dd/MM/yyyy HH:mm');
  } catch (error) {
    return value;
  }
};

export const startOfMonthIso = () => format(startOfMonth(new Date()), 'yyyy-MM-dd');
