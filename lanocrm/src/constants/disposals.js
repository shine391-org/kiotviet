import { format, startOfMonth } from 'date-fns';

export const DISPOSAL_STATUSES = [
  { value: 'draft', label: 'Phiếu tạm', color: 'gold' },
  { value: 'completed', label: 'Hoàn thành', color: 'green' },
  { value: 'cancelled', label: 'Đã hủy', color: 'red' },
];

export const DEFAULT_DISPOSAL_COLUMNS = [
  'dispose_code',
  'total_value',
  'disposed_at',
  'branch_name',
  'creator_name',
  'executor_name',
  'notes',
  'status',
];

export const formatDisposalDate = (value) => {
  if (!value) return '—';
  try {
    return format(new Date(value), 'dd/MM/yyyy HH:mm');
  } catch (error) {
    return value;
  }
};

export const startOfMonthIso = () => format(startOfMonth(new Date()), 'yyyy-MM-dd');
