import React, { useMemo } from 'react';
import { Table, Tag, Dropdown, Checkbox, Button, Space, Typography } from 'antd';
import { DownOutlined, InfoCircleOutlined } from '@ant-design/icons';
import { SHIPMENT_STATUSES, formatDateTime } from '../../constants/shipments';

const statusMap = SHIPMENT_STATUSES.reduce((acc, s) => ({ ...acc, [s.value]: s }), {});

const columnCatalog = {
  code: { title: 'Mã vận đơn', dataIndex: 'code', width: 150 },
  created_at: {
    title: 'Thời gian tạo',
    dataIndex: 'created_at',
    width: 170,
    sorter: true,
    render: (v) => formatDateTime(v),
  },
  invoice_code: { title: 'Mã hóa đơn', dataIndex: 'invoice_code', width: 140 },
  customer_name: { title: 'Khách hàng', dataIndex: 'customer_name', ellipsis: true },
  delivery_partner: {
    title: 'Đối tác giao hàng',
    dataIndex: 'delivery_partner_name',
    render: (v, r) => v || r.delivery_partner || '—',
    width: 150,
  },
  delivery_status: {
    title: 'Trạng thái giao',
    dataIndex: 'delivery_status',
    width: 150,
    render: (v, r) => {
      const meta = statusMap[v] || {};
      return (
        <Space size={4}>
          <Tag color={meta.color || 'default'}>{meta.label || v || '—'}</Tag>
          {r.status_note ? <InfoCircleOutlined style={{ color: '#999' }} title={r.status_note} /> : null}
        </Space>
      );
    },
  },
  delivery_time: {
    title: 'Thời gian giao hàng',
    dataIndex: 'delivery_time',
    width: 170,
    sorter: true,
    render: (v) => formatDateTime(v),
  },
  cod_amount: {
    title: 'Cần thu hộ (COD)',
    dataIndex: 'cod_amount',
    width: 150,
    sorter: true,
    align: 'right',
    render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
  },
};

const ShipmentTable = ({
  data,
  loading,
  pagination,
  onPageChange,
  onSelectRow,
  selectedRowKey,
  visibleColumns,
  onToggleColumn,
  summary,
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

  const menu = { items: columnMenuItems };

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8 }}>
        <Space>
          <Typography.Text strong>Danh sách vận đơn</Typography.Text>
          <Typography.Text type="secondary">
            Tổng COD: {(summary?.cod_total || 0).toLocaleString('vi-VN')} đ
          </Typography.Text>
        </Space>
        <Dropdown menu={menu} trigger={['click']}>
          <Button size="small">
            Ẩn/hiện cột <DownOutlined />
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
        }}
        onChange={(pager, _filters, sorter) => {
          const sortField = sorter?.field || sorter?.columnKey;
          const sortOrder = sorter?.order ? (sorter.order === 'ascend' ? 'asc' : 'desc') : undefined;
          onPageChange({
            page: pager.current,
            limit: pager.pageSize,
            sort: sortField ? `${sortField},${sortOrder || 'desc'}` : undefined,
          });
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

export default ShipmentTable;
