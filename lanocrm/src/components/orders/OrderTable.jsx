import React, { useMemo } from 'react';
import { Table, Tag, Dropdown, Checkbox, Button, Space, Typography } from 'antd';
import { DownOutlined } from '@ant-design/icons';
import { ORDER_STATUSES, formatDate } from '../../constants/orders';

const statusMap = ORDER_STATUSES.reduce((acc, s) => ({ ...acc, [s.value]: s }), {});

const columnCatalog = {
  order_number: { title: 'Mã đặt hàng', dataIndex: 'order_number' },
  order_date: {
    title: 'Thời gian',
    dataIndex: 'order_date',
    render: (v) => formatDate(v),
    width: 160,
  },
  customer_name: { title: 'Khách hàng', dataIndex: 'customer_name' },
  shipping_phone: { title: 'Điện thoại', dataIndex: 'shipping_phone', width: 140 },
  shipping_address: { title: 'Địa chỉ', dataIndex: 'shipping_address', ellipsis: true },
  branch_name: { title: 'Chi nhánh', dataIndex: 'branch_name', width: 140 },
  total: {
    title: 'Khách cần trả',
    dataIndex: 'total',
    align: 'right',
    render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
    width: 140,
  },
  paid_amount: {
    title: 'Khách đã trả',
    dataIndex: 'paid_amount',
    align: 'right',
    render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
    width: 140,
  },
  debt_amount: {
    title: 'Còn nợ',
    dataIndex: 'debt_amount',
    align: 'right',
    render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
    width: 120,
  },
  status: {
    title: 'Trạng thái',
    dataIndex: 'status',
    width: 140,
    render: (v) => {
      const meta = statusMap[v] || { label: v, color: 'default' };
      return <Tag color={meta.color}>{meta.label || v || '—'}</Tag>;
    },
  },
};

const OrderTable = ({
  data,
  loading,
  pagination,
  onPageChange,
  onSelectRow,
  selectedRowKey,
  visibleColumns,
  onToggleColumn,
}) => {
  const columns = useMemo(() => {
    return visibleColumns
      .map((key) => ({ key, ...columnCatalog[key] }))
      .filter(Boolean);
  }, [visibleColumns]);

  const columnMenuItems = Object.keys(columnCatalog).map((key) => ({
    key,
    label: (
      <Checkbox
        checked={visibleColumns.includes(key)}
        onChange={(e) => onToggleColumn(key, e.target.checked)}
      >
        {columnCatalog[key].title}
      </Checkbox>
    ),
  }));

  const menu = {
    items: columnMenuItems,
  };

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8 }}>
        <Typography.Text strong>Danh sách đơn hàng</Typography.Text>
        <Dropdown menu={menu} trigger={["click"]}>
          <Button size="small">
            Cột hiển thị <DownOutlined />
          </Button>
        </Dropdown>
      </div>
      <Table
        rowKey="id"
        dataSource={data}
        columns={columns}
        loading={loading}
        pagination={{
          current: pagination?.page || 1,
          pageSize: pagination?.limit || 15,
          total: pagination?.total || 0,
          showSizeChanger: true,
          onChange: (page, pageSize) => onPageChange({ page, limit: pageSize }),
        }}
        onRow={(record) => ({
          onClick: () => onSelectRow && onSelectRow(record),
        })}
        rowClassName={(record) => (record.id === selectedRowKey ? 'table-row-selected' : '')}
        size="middle"
      />
    </div>
  );
};

export default OrderTable;
