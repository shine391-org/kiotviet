import { format } from 'date-fns';

export const TRANSFER_STATUSES = [
  { value: 'draft', label: 'Phiếu tạm', color: 'default' },
  { value: 'in_transit', label: 'Đang chuyển', color: 'blue' },
  { value: 'received', label: 'Đã nhận', color: 'green' },
  { value: 'cancelled', label: 'Đã hủy', color: 'red' },
];

export const RECEIVING_STATUS_OPTIONS = [
  { value: 'all', label: 'Tất cả' },
  { value: 'mismatch', label: 'Không khớp' },
  { value: 'matched', label: 'Khớp' },
];

export const DEFAULT_TRANSFER_COLUMNS = [
  'favorite',
  'code',
  'creatorName',
  'receiverName',
  'transferDate',
  'receiveDate',
  'createdAt',
  'fromBranch',
  'toBranch',
  'quantitySent',
  'valueSent',
  'quantityReceived',
  'valueReceived',
  'totalItems',
  'notes',
  'status',
];

export const TRANSFER_RECEIVING_STATUS_BADGES = {
  draft: { color: 'default', text: 'Phiếu tạm' },
  in_transit: { color: 'blue', text: 'Đang chuyển' },
  received: { color: 'green', text: 'Đã nhận' },
  cancelled: { color: 'red', text: 'Đã hủy' },
};

export const formatDateTime = (value) => {
  if (!value) return '—';
  try {
    return format(new Date(value), 'dd/MM/yyyy HH:mm');
  } catch (e) {
    return value;
  }
};

export const formatNumber = (value) =>
  typeof value === 'number'
    ? value.toLocaleString('vi-VN', { maximumFractionDigits: 2 })
    : '—';

export const formatCurrency = (value) =>
  typeof value === 'number'
    ? value.toLocaleString('vi-VN')
    : '—';
