import { format } from 'date-fns';

export const SHIPMENT_STATUSES = [
  { value: 'pending', label: 'Chờ xử lý', color: 'gold' },
  { value: 'picking', label: 'Lấy hàng', color: 'blue' },
  { value: 'delivering', label: 'Đang giao', color: 'geekblue' },
  { value: 'delivered', label: 'Giao thành công', color: 'green' },
  { value: 'returned', label: 'Chuyển hoàn', color: 'volcano' },
  { value: 'return_completed', label: 'Đã chuyển hoàn', color: 'purple' },
  { value: 'cancelled', label: 'Đã hủy', color: 'default' },
];

export const COD_FILTERS = [
  { value: 'all', label: 'Tất cả' },
  { value: 'yes', label: 'Có' },
  { value: 'no', label: 'Không' },
];

export const DEFAULT_SHIPMENT_COLUMNS = [
  'code',
  'created_at',
  'completed_at',
  'created_by',
  'invoice_code',
  'customer_name',
  'branch_name',
  'salesperson',
  'delivery_status',
];

export const DELIVERY_PARTNERS = [
  { value: 'ghn', label: 'Giao hàng nhanh' },
  { value: 'ahamove', label: 'AhaMove' },
  { value: 'xanh_sm', label: 'Xanh SM' },
  { value: 'viettel_post', label: 'Viettel Post' },
  { value: 'manual', label: 'KiotViet' },
];

export const DELIVERY_AREAS = [
  {
    value: 'hn',
    label: 'Hà Nội',
    children: [
      { value: 'ba_dinh', label: 'Ba Đình' },
      { value: 'dong_da', label: 'Đống Đa' },
      { value: 'cau_giay', label: 'Cầu Giấy' },
    ],
  },
  {
    value: 'ls',
    label: 'Lạng Sơn',
    children: [
      { value: 'cao_loc', label: 'Hữu Lũng' },
      { value: 'lang_son_tp', label: 'TP Lạng Sơn' },
    ],
  },
  {
    value: 'hcm',
    label: 'TP.HCM',
    children: [
      { value: 'q1', label: 'Quận 1' },
      { value: 'q3', label: 'Quận 3' },
      { value: 'tan_binh', label: 'Tân Bình' },
    ],
  },
];

export const formatDateTime = (value) => {
  if (!value) return '—';
  try {
    return format(new Date(value), 'dd/MM/yyyy HH:mm');
  } catch (e) {
    return value;
  }
};

export const formatDate = (value) => {
  if (!value) return '—';
  try {
    return format(new Date(value), 'dd/MM/yyyy');
  } catch (e) {
    return value;
  }
};
