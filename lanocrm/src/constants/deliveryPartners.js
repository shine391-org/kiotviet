export const DELIVERY_PARTNER_COLUMNS = [
  { key: 'code', label: 'Mã đối tác' },
  { key: 'name', label: 'Tên đối tác' },
  { key: 'phone', label: 'Điện thoại' },
  { key: 'email', label: 'Email' },
  { key: 'total_orders', label: 'Tổng đơn hàng' },
  { key: 'cod_collect', label: 'Cần thu hộ (COD)' },
  { key: 'cod_remain', label: 'Còn cần thu (COD)' },
  { key: 'total_weight', label: 'Tổng trọng lượng' },
  { key: 'current_debt', label: 'Nợ cần trả hiện tại' },
  { key: 'total_shipping_fee', label: 'Tổng phí giao hàng' },
  { key: 'status', label: 'Trạng thái' },
];

export const DELIVERY_PARTNER_GROUPS = [
  { value: 'all', label: 'Tất cả các nhóm' },
  { value: 'fast', label: 'Giao nhanh' },
  { value: 'economy', label: 'Tiết kiệm' },
  { value: 'internal', label: 'Nội bộ' },
];

export const DELIVERY_PARTNER_STATUSES = [
  { value: 'all', label: 'Tất cả' },
  { value: 'active', label: 'Đang hoạt động' },
  { value: 'inactive', label: 'Ngừng hoạt động' },
];

export const DELIVERY_PARTNER_TABS = [
  { value: 'integrated', label: 'Tích hợp' },
  { value: 'other', label: 'Khác' },
];

export const DELIVERY_PARTNER_PAGE_SIZES = [15, 25, 50, 100];

export const defaultVisibleColumns = [
  'code',
  'name',
  'phone',
  'email',
  'total_orders',
  'current_debt',
  'total_shipping_fee',
  'status',
];
