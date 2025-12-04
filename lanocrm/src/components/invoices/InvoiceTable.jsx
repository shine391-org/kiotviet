import React, { useMemo } from 'react';
import { Table, Tag, Dropdown, Checkbox, Button, Space, Typography } from 'antd';
import { SettingOutlined, StarOutlined } from '@ant-design/icons';
import {
  INVOICE_STATUSES,
  DELIVERY_STATUSES,
  formatDateTime,
} from '../../constants/invoices';

const statusMap = INVOICE_STATUSES.reduce((acc, s) => ({ ...acc, [s.value]: s }), {});
const deliveryStatusMap = DELIVERY_STATUSES.reduce((acc, s) => ({ ...acc, [s.value]: s }), {});

const currency = (value) => (Number(value || 0)).toLocaleString('vi-VN');

export const invoiceColumnCatalog = {
  invoice_code: { title: 'Mã hóa đơn', dataIndex: 'invoice_code', width: 140, sorter: true, fixed: 'left' },
  shipment_code: { title: 'Mã vận đơn', dataIndex: 'shipment_code', width: 140 },
  delivery_status: {
    title: 'Trạng thái giao hàng',
    dataIndex: 'delivery_status',
    width: 160,
    render: (v) => {
      const meta = deliveryStatusMap[v] || {};
      return <Tag color={meta.color}>{meta.label || v || '—'}</Tag>;
    },
  },
  reconciliation_code: { title: 'Mã đối soát', dataIndex: 'reconciliation_code', width: 140 },
  issued_at: { title: 'Thời gian', dataIndex: 'issued_at', width: 170, render: formatDateTime, sorter: true },
  created_at: { title: 'Thời gian tạo', dataIndex: 'created_at', width: 170, render: formatDateTime },
  updated_at: { title: 'Ngày cập nhật', dataIndex: 'updated_at', width: 170, render: formatDateTime },
  order_code: { title: 'Mã đặt hàng', dataIndex: 'order_code', width: 140 },
  return_code: { title: 'Mã trả hàng', dataIndex: 'return_code', width: 140 },
  warranty_code: { title: 'Mã YCSC', dataIndex: 'warranty_code', width: 140 },
  customer_code: { title: 'Mã KH', dataIndex: 'customer_code', width: 120 },
  customer_name: { title: 'Khách hàng', dataIndex: 'customer_name', width: 160 },
  email: { title: 'Email', dataIndex: 'email', width: 180, ellipsis: true },
  phone: { title: 'Điện thoại', dataIndex: 'phone', width: 140 },
  address: { title: 'Địa chỉ', dataIndex: 'address', ellipsis: true, width: 240 },
  region: { title: 'Khu vực', dataIndex: 'region', width: 160 },
  ward: { title: 'Phường/Xã', dataIndex: 'ward', width: 140 },
  birth_date: { title: 'Ngày sinh', dataIndex: 'birth_date', width: 140, render: formatDateTime },
  branch_name: { title: 'Chi nhánh', dataIndex: 'branch_name', width: 140 },
  seller_name: { title: 'Người bán', dataIndex: 'seller_name', width: 140 },
  creator_name: { title: 'Người tạo', dataIndex: 'creator_name', width: 140 },
  sales_channel: { title: 'Kênh bán', dataIndex: 'sales_channel', width: 140 },
  shipping_partner: { title: 'Đối tác giao hàng', dataIndex: 'shipping_partner', width: 180 },
  note: { title: 'Ghi chú', dataIndex: 'note', ellipsis: true, width: 200 },
  goods_total: { title: 'Tổng tiền hàng', dataIndex: 'goods_total', width: 150, align: 'right', render: currency },
  discount_total: { title: 'Giảm giá', dataIndex: 'discount_total', width: 120, align: 'right', render: currency },
  net_total: { title: 'Tổng sau giảm giá', dataIndex: 'net_total', width: 160, align: 'right', render: currency },
  tax_discount: { title: 'Giảm thuế', dataIndex: 'tax_discount', width: 120, align: 'right', render: currency },
  other_fee: { title: 'Thu khác', dataIndex: 'other_fee', width: 120, align: 'right', render: currency },
  customer_payable: { title: 'Khách cần trả', dataIndex: 'customer_payable', width: 150, align: 'right', render: currency },
  customer_paid: { title: 'Khách đã trả', dataIndex: 'customer_paid', width: 150, align: 'right', render: currency },
  payment_discount: { title: 'Chiết khấu thanh toán', dataIndex: 'payment_discount', width: 170, align: 'right', render: currency },
  cod_amount: { title: 'Còn cần thu (COD)', dataIndex: 'cod_amount', width: 170, align: 'right', render: currency },
  shipping_fee: { title: 'Phí trả ĐTGH', dataIndex: 'shipping_fee', width: 140, align: 'right', render: currency },
  delivery_note: { title: 'Ghi chú trạng thái giao hàng', dataIndex: 'delivery_note', width: 220, ellipsis: true },
  delivery_time: { title: 'Thời gian giao hàng', dataIndex: 'delivery_time', width: 170, render: formatDateTime },
  invoice_status: {
    title: 'Trạng thái',
    dataIndex: 'invoice_status',
    width: 140,
    fixed: 'right',
    render: (v) => {
      const meta = statusMap[v] || {};
      return <Tag color={meta.color}>{meta.label || v || '—'}</Tag>;
    },
  },
};

const InvoiceTable = ({
  data,
  loading,
  pagination,
  onPageChange,
  onSelectRow,
  selectedRowKey,
  visibleColumns,
  onToggleColumn,
}) => {
  const columns = useMemo(
    () =>
      visibleColumns
        .map((key) => ({ key, ...invoiceColumnCatalog[key] }))
        .filter(Boolean),
    [visibleColumns]
  );

  const columnMenuItems = Object.keys(invoiceColumnCatalog).map((key) => ({
    key,
    label: (
      <Checkbox
        checked={visibleColumns.includes(key)}
        onChange={(e) => onToggleColumn(key, e.target.checked)}
      >
        {invoiceColumnCatalog[key].title}
      </Checkbox>
    ),
  }));

  const menu = { items: columnMenuItems };

  const currentPage = pagination?.page || 1;
  const pageSize = pagination?.limit || 15;
  const total = pagination?.total || 0;
  const start = total === 0 ? 0 : (currentPage - 1) * pageSize + 1;
  const end = Math.min(total, currentPage * pageSize);

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8 }}>
        <Space>
          <StarOutlined style={{ color: '#1677ff' }} />
          <Typography.Text strong>Danh sách hóa đơn</Typography.Text>
        </Space>
        <Dropdown menu={menu} trigger={['click']}>
          <Button size="small" icon={<SettingOutlined />}>Cột hiển thị</Button>
        </Dropdown>
      </div>

      <Table
        rowKey={(record) => record.id || record.invoice_code}
        dataSource={data}
        columns={columns}
        loading={loading}
        rowSelection={{
          selectedRowKeys: selectedRowKey ? [selectedRowKey] : [],
          onChange: (_keys, rows) => {
            if (rows && rows[0]) {
              onSelectRow && onSelectRow(rows[0]);
            }
          },
        }}
        pagination={{
          current: currentPage,
          pageSize,
          total,
          showSizeChanger: true,
          showTotal: () => `${start} - ${end} trong ${total} giao dịch`,
          onChange: (page, size) => onPageChange({ page, limit: size }),
        }}
        onRow={(record) => ({
          onClick: () => onSelectRow && onSelectRow(record),
        })}
        rowClassName={(record) => (record.id === selectedRowKey ? 'table-row-selected' : '')}
        scroll={{ x: 1800 }}
        size="middle"
      />
    </div>
  );
};

export default InvoiceTable;
