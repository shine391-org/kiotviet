import React, { useMemo, useState } from 'react';
import { Table, Tag } from 'antd';
import { ORDER_STATUSES, formatDate } from '../../constants/orders';
import OrderExpandedRow from './OrderExpandedRow';

const statusMap = ORDER_STATUSES.reduce((acc, s) => ({ ...acc, [s.value]: s }), {});

const columnCatalog = {
  // Left column of dropdown
  tracking_code: {
    title: 'Mã vận đơn',
    dataIndex: 'tracking_code',
    width: 140,
    render: (v) => v || '—',
  },
  order_number: { title: 'Mã đặt hàng', dataIndex: 'order_number', width: 140 },
  invoice_code: {
    title: 'Mã hóa đơn',
    dataIndex: 'invoice_code',
    width: 140,
    render: (v) => v || '—',
  },
  order_date: {
    title: 'Thời gian',
    dataIndex: 'order_date',
    render: (v) => formatDate(v),
    width: 160,
  },
  created_at: {
    title: 'Thời gian tạo',
    dataIndex: 'created_at',
    render: (v) => formatDate(v),
    width: 160,
  },
  updated_at: {
    title: 'Ngày cập nhật',
    dataIndex: 'updated_at',
    render: (v) => formatDate(v),
    width: 160,
  },
  delivery_date: {
    title: 'Thời gian giao hàng',
    dataIndex: 'delivery_date',
    render: (v) => formatDate(v),
    width: 160,
  },
  waiting_days: {
    title: 'Số ngày chờ',
    dataIndex: 'waiting_days',
    width: 100,
    align: 'right',
    render: (v) => v ?? '—',
  },
  customer_name: { title: 'Khách hàng', dataIndex: 'customer_name', width: 160 },
  shipping_phone: { title: 'Điện thoại', dataIndex: 'shipping_phone', width: 140 },
  shipping_address: { title: 'Địa chỉ', dataIndex: 'shipping_address', ellipsis: true, width: 200 },
  region: { title: 'Khu vực', dataIndex: 'region', width: 140, render: (v) => v || '—' },
  ward: { title: 'Phường/Xã', dataIndex: 'ward', width: 140, render: (v) => v || '—' },

  // Right column of dropdown
  birth_date: {
    title: 'Ngày sinh',
    dataIndex: 'birth_date',
    render: (v) => formatDate(v),
    width: 120,
  },
  shipping_partner: { title: 'Đối tác giao hàng', dataIndex: 'shipping_partner', width: 160, render: (v) => v || '—' },
  receiver: { title: 'Người nhận đặt', dataIndex: 'receiver', width: 140, render: (v) => v || '—' },
  created_by: { title: 'Người tạo', dataIndex: 'created_by', width: 140, render: (v) => v || '—' },
  sales_channel: { title: 'Kênh bán', dataIndex: 'sales_channel', width: 120, render: (v) => v || '—' },
  note: { title: 'Ghi chú', dataIndex: 'note', ellipsis: true, width: 200, render: (v) => v || '—' },
  subtotal: {
    title: 'Tổng tiền hàng',
    dataIndex: 'subtotal',
    align: 'right',
    render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
    width: 140,
  },
  discount: {
    title: 'Giảm giá',
    dataIndex: 'discount',
    align: 'right',
    render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
    width: 120,
  },
  total_after_discount: {
    title: 'Tổng sau giảm giá',
    dataIndex: 'total_after_discount',
    align: 'right',
    render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
    width: 150,
  },
  other_fee: {
    title: 'Thu khác',
    dataIndex: 'other_fee',
    align: 'right',
    render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
    width: 120,
  },
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
  onSaveOrder,
  onProcessOrder,
  onCancelOrder,
}) => {
  const [expandedRowKeys, setExpandedRowKeys] = useState([]);

  const columns = useMemo(() => {
    return visibleColumns
      .map((key) => ({ key, ...columnCatalog[key] }))
      .filter(Boolean);
  }, [visibleColumns]);

  const handleRowClick = (record) => {
    // Toggle expansion
    if (expandedRowKeys.includes(record.id)) {
      setExpandedRowKeys([]);
    } else {
      setExpandedRowKeys([record.id]);
    }
    // Also call parent handler if provided
    if (onSelectRow) {
      onSelectRow(record);
    }
  };

  // Default handlers - fallback to no-op if props not provided
  const handleSave = (record) => {
    if (onSaveOrder) {
      onSaveOrder(record);
    }
  };

  const handleProcess = (record) => {
    if (onProcessOrder) {
      onProcessOrder(record);
    }
  };

  const handleCancel = (record) => {
    if (onCancelOrder) {
      onCancelOrder(record);
    } else {
      // Default behavior: collapse expanded row
      setExpandedRowKeys([]);
    }
  };

  return (
    <div>
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
          onClick: () => handleRowClick(record),
          style: { cursor: 'pointer' },
        })}
        rowClassName={(record) => (record.id === selectedRowKey ? 'table-row-selected' : '')}
        size="middle"
        expandable={{
          expandedRowKeys,
          onExpand: (expanded, record) => {
            if (expanded) {
              setExpandedRowKeys([record.id]);
            } else {
              setExpandedRowKeys([]);
            }
          },
          expandedRowRender: (record) => (
            <OrderExpandedRow
              record={record}
              onSave={() => handleSave(record)}
              onCancel={() => handleCancel(record)}
              onProcess={() => handleProcess(record)}
            />
          ),
          expandRowByClick: true,
        }}
      />
    </div>
  );
};

export default OrderTable;
export { columnCatalog };
