import { format, startOfMonth } from 'date-fns';

export const INVOICE_TYPES = [
  { value: 'pickup', label: 'Không giao hàng' },
  { value: 'delivery', label: 'Giao hàng' },
];

export const INVOICE_STATUSES = [
  { value: 'processing', label: 'Đang xử lý', color: 'blue' },
  { value: 'completed', label: 'Hoàn thành', color: 'green' },
  { value: 'failed_delivery', label: 'Không giao được', color: 'volcano' },
  { value: 'cancelled', label: 'Đã hủy', color: 'red' },
];

export const E_INVOICE_STATUSES = [
  { value: 'pending', label: 'Chờ phát hành' },
  { value: 'issued', label: 'Đã phát hành' },
  { value: 'rejected', label: 'Từ chối' },
  { value: 'canceled', label: 'Đã hủy' },
];

export const DELIVERY_STATUSES = [
  { value: 'pending', label: 'Chờ xử lý', color: 'orange' },
  { value: 'shipping', label: 'Đang giao hàng', color: 'geekblue' },
  { value: 'delivered', label: 'Giao thành công', color: 'green' },
  { value: 'failed', label: 'Giao thất bại', color: 'volcano' },
  { value: 'returning', label: 'Đang hoàn', color: 'gold' },
];

export const SHIPPING_PARTNERS = [
  { value: 'ghn', label: 'Giao hàng nhanh' },
  { value: 'ghtk', label: 'GHTK' },
  { value: 'ahamove', label: 'AhaMove' },
  { value: 'viettel_post', label: 'Viettel Post' },
  { value: 'grab', label: 'GrabExpress' },
  { value: 'internal', label: 'Tự giao / nội bộ' },
];

export const PAYMENT_METHODS = [
  { value: 'CASH', label: 'Tiền mặt' },
  { value: 'BANK_TRANSFER', label: 'Chuyển khoản' },
  { value: 'CARD', label: 'Thẻ' },
  { value: 'COD', label: 'Thu hộ (COD)' },
  { value: 'EWALLET', label: 'Ví điện tử' },
];

export const SALES_CHANNELS = [
  { value: 'retail', label: 'Bán trực tiếp' },
  { value: 'online', label: 'Bán online' },
  { value: 'facebook', label: 'Facebook' },
  { value: 'tiktok', label: 'TikTok Shop' },
  { value: 'shopee', label: 'Shopee' },
];

export const DEFAULT_INVOICE_COLUMNS = [
  'invoice_code',
  'shipment_code',
  'delivery_status',
  'reconciliation_code',
  'issued_at',
  'created_at',
  'updated_at',
  'order_code',
  'return_code',
  'warranty_code',
  'customer_code',
  'customer_name',
  'email',
  'phone',
  'address',
  'region',
  'ward',
  'branch_name',
  'seller_name',
  'creator_name',
  'sales_channel',
  'shipping_partner',
  'goods_total',
  'discount_total',
  'net_total',
  'tax_discount',
  'other_fee',
  'customer_payable',
  'customer_paid',
  'cod_amount',
  'shipping_fee',
  'delivery_note',
  'delivery_time',
  'invoice_status',
];

export const formatDateTime = (value) => {
  if (!value) return '—';
  try {
    return format(new Date(value), 'dd/MM/yyyy HH:mm');
  } catch (error) {
    return value;
  }
};

export const startOfMonthIso = () => format(startOfMonth(new Date()), 'yyyy-MM-dd');
