import { format } from 'date-fns';

export const STOCK_AUDIT_STATUSES = [
  { value: 'draft', label: 'Phiếu tạm', color: 'gold' },
  { value: 'balanced', label: 'Đã cân bằng kho', color: 'green' },
  { value: 'cancelled', label: 'Đã hủy', color: 'red' },
];

export const DEFAULT_STOCK_AUDIT_COLUMNS = [
  'favorite',
  'code',
  'createdTime',
  'creatorName',
  'reconciledBy',
  'reconciledDate',
  'actualQuantity',
  'totalActualValue',
  'differenceQuantity',
  'differenceValue',
  'overCountedQty',
  'overCountedValue',
  'underCountedQty',
  'underCountedValue',
  'notes',
  'status',
];

export const STOCK_AUDIT_COLUMN_LABELS = {
  favorite: 'Ưa thích',
  code: 'Mã kiểm kho',
  createdTime: 'Thời gian',
  creatorName: 'Người tạo',
  reconciledBy: 'Người cân bằng',
  reconciledDate: 'Ngày cân bằng',
  actualQuantity: 'SL thực tế',
  totalActualValue: 'Tổng thực tế',
  differenceQuantity: 'Tổng chênh lệch',
  differenceValue: 'Tổng giá trị lệch',
  overCountedQty: 'SL lệch tăng',
  overCountedValue: 'Tổng giá trị tăng',
  underCountedQty: 'SL lệch giảm',
  underCountedValue: 'Tổng giá trị giảm',
  notes: 'Ghi chú',
  status: 'Trạng thái',
};

export const formatAuditDateTime = (value) => {
  if (!value) return '—';
  try {
    return format(new Date(value), 'dd/MM/yyyy HH:mm');
  } catch (e) {
    return value;
  }
};

export const formatAuditNumber = (value) =>
  typeof value === 'number'
    ? value.toLocaleString('vi-VN', { maximumFractionDigits: 2 })
    : '—';

export const formatAuditCurrency = (value) =>
  typeof value === 'number'
    ? value.toLocaleString('vi-VN')
    : '—';

