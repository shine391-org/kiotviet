import { startOfYear, startOfMonth, format } from 'date-fns';

export const ORDER_STATUSES = [
  { value: 'draft', label: 'Phiếu tạm', color: 'orange' },
  { value: 'confirmed', label: 'Đã xác nhận', color: 'blue' },
  { value: 'processing', label: 'Đang xử lý', color: 'cyan' },
  { value: 'shipping', label: 'Đang giao hàng', color: 'geekblue' },
  { value: 'delivered', label: 'Đã giao', color: 'green' },
  { value: 'completed', label: 'Hoàn thành', color: 'green' },
  { value: 'failed', label: 'Giao thất bại', color: 'volcano' },
  { value: 'cancelled', label: 'Đã hủy', color: 'red' },
  { value: 'return', label: 'Đang hoàn', color: 'gold' },
  { value: 'return_confirmed', label: 'Đã hoàn', color: 'lime' },
];

export const PAYMENT_METHODS = [
  { value: 'CASH', label: 'Tiền mặt' },
  { value: 'BANK_TRANSFER', label: 'Chuyển khoản' },
  { value: 'CARD', label: 'Thẻ' },
  { value: 'COD', label: 'Thu hộ (COD)' },
  { value: 'E_WALLET', label: 'Ví điện tử' },
];

export const DEFAULT_ORDER_COLUMNS = [
  'order_number',
  'order_code',
  'invoice_code',
  'order_date',
  'created_at',
  'updated_at',
  'shipping_date',
  'lead_time_days',
  'customer_name',
  'shipping_phone',
  'shipping_address',
  'shipping_city',
  'shipping_district',
  'shipping_ward',
  'creator',
  'receiver',
  'channel',
  'notes',
  'subtotal',
  'discount_total',
  'total',
  'cod_amount',
  'debt_amount',
  'paid_amount',
  'status',
];

export const formatDate = (value) => {
  if (!value) return '—';
  try {
    return format(new Date(value), 'dd/MM/yyyy HH:mm');
  } catch (e) {
    return value;
  }
};

export const startOfYearIso = () => format(startOfYear(new Date()), 'yyyy-MM-dd');
export const startOfMonthIso = () => format(startOfMonth(new Date()), 'yyyy-MM-dd');

export const SHIPPING_PARTNERS = [
  { value: 'ghn', label: 'Giao hàng nhanh', status: 'active' },
  { value: 'ghtk', label: 'GHTK', status: 'active' },
  { value: 'ahamove', label: 'Ahamove', status: 'active' },
  { value: 'viettel_post', label: 'Viettel Post', status: 'inactive' },
  { value: 'manual', label: 'Tự giao / nội bộ', status: 'active' },
];

export const SALES_CHANNELS = [
  { value: 'retail', label: 'Bán lẻ tại quầy' },
  { value: 'online', label: 'Bán online' },
  { value: 'facebook', label: 'Facebook' },
  { value: 'tiktok', label: 'TikTok Shop' },
  { value: 'shopee', label: 'Shopee' },
];
