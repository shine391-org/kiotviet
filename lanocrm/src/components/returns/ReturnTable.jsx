import React, { useMemo } from 'react';
import { Table, Tag, Dropdown, Checkbox, Button, Typography, Spin } from 'antd';
import { DownOutlined } from '@ant-design/icons';
import { RETURN_STATUSES, formatReturnDate } from '../../constants/returns';
import ReturnDetailPanel from './ReturnDetailPanel';

const statusMap = RETURN_STATUSES.reduce((acc, s) => ({ ...acc, [s.value]: s }), {});

export const returnColumnCatalog = {
  return_code: { title: 'Mã trả hàng', dataIndex: 'return_code' },
  invoice_code: { title: 'Mã hóa đơn', dataIndex: 'invoice_code' },
  shipping_code: { title: 'Mã vận đơn bán', dataIndex: 'shipping_code', ellipsis: true },
  seller_name: { title: 'Người bán', dataIndex: 'seller_name' },
  return_time: { title: 'Thời gian', dataIndex: 'return_time', render: (v) => formatReturnDate(v), width: 160 },
  created_at: { title: 'Thời gian tạo', dataIndex: 'created_at', render: (v) => formatReturnDate(v), width: 160 },
  customer_name: { title: 'Khách hàng', dataIndex: 'customer_name' },
  branch_name: { title: 'Chi nhánh', dataIndex: 'branch_name' },
  receiver_name: { title: 'Người nhận trả', dataIndex: 'receiver_name' },
  creator_name: { title: 'Người tạo', dataIndex: 'creator_name' },
  channel: { title: 'Kênh bán', dataIndex: 'channel' },
  notes: { title: 'Ghi chú', dataIndex: 'notes', ellipsis: true },
  goods_amount: {
    title: 'Tổng tiền hàng',
    dataIndex: 'goods_amount',
    align: 'right',
    render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
    width: 140,
  },
  discount_amount: {
    title: 'Giảm giá',
    dataIndex: 'discount_amount',
    align: 'right',
    render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
    width: 120,
  },
  net_amount: {
    title: 'Tổng sau giảm giá',
    dataIndex: 'net_amount',
    align: 'right',
    render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
    width: 150,
  },
  tax_refund: {
    title: 'Giảm thuế hoàn lại',
    dataIndex: 'tax_refund',
    align: 'right',
    render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
    width: 150,
  },
  return_fee: {
    title: 'Phí trả hàng',
    dataIndex: 'return_fee',
    align: 'right',
    render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
    width: 130,
  },
  other_refund: {
    title: 'Thu khác hoàn lại',
    dataIndex: 'other_refund',
    align: 'right',
    render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
    width: 150,
  },
  need_refund: {
    title: 'Cần trả khách',
    dataIndex: 'need_refund',
    align: 'right',
    render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
    width: 140,
  },
  refunded_amount: {
    title: 'Đã trả khách',
    dataIndex: 'refunded_amount',
    align: 'right',
    render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
    width: 140,
  },
  status: {
    title: 'Trạng thái',
    dataIndex: 'status',
    width: 120,
    render: (v) => {
      const meta = statusMap[v] || { label: v, color: 'default' };
      return <Tag color={meta.color}>{meta.label || v || '—'}</Tag>;
    },
  },
};

const ReturnTable = ({
  data,
  loading,
  pagination,
  onPageChange,
  onSelectRow,
  selectedRowKey,
  visibleColumns,
  onToggleColumn,
  detailData,
  detailLoading,
}) => {
  const columns = useMemo(() => {
    return visibleColumns
      .map((key) => ({ key, ...returnColumnCatalog[key] }))
      .filter(Boolean);
  }, [visibleColumns]);

  const columnMenuItems = Object.keys(returnColumnCatalog).map((key) => ({
    key,
    label: (
      <Checkbox
        checked={visibleColumns.includes(key)}
        onChange={(e) => onToggleColumn(key, e.target.checked)}
      >
        {returnColumnCatalog[key]?.title || key}
      </Checkbox>
    ),
  }));

  const menu = { items: columnMenuItems };

  const getRowKey = (record) => record.id || record.return_code;

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8 }}>
        <Typography.Text strong>Danh sách phiếu trả</Typography.Text>
        <Dropdown menu={menu} trigger={["click"]}>
          <Button size="small">
            Cột hiển thị <DownOutlined />
          </Button>
        </Dropdown>
      </div>
      <Table
        rowKey={getRowKey}
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
        rowSelection={{
          type: 'checkbox',
          selectedRowKeys: selectedRowKey ? [selectedRowKey] : [],
          onChange: (keys) => {
            const id = keys[keys.length - 1];
            if (id && id !== selectedRowKey) {
              const record = data.find((r) => getRowKey(r) === id);
              if (record) onSelectRow(record);
            }
          },
        }}
        expandable={{
          expandedRowKeys: selectedRowKey ? [selectedRowKey] : [],
          expandIcon: () => null, // Hide default expand icon
          expandedRowRender: (record) => {
            // Use same key resolution as getRowKey for consistent identity
            const recordKey = record?.id || record?.return_code;
            const detailKey = detailData?.id || detailData?.return_code;
            return (
              <Spin spinning={detailLoading}>
                <ReturnDetailPanel
                  data={detailKey === recordKey ? detailData : record}
                />
              </Spin>
            );
          },
        }}
        onRow={(record) => ({
          onClick: () => {
            const key = getRowKey(record);
            if (key === selectedRowKey) {
              // Click same row = collapse
              onSelectRow(null);
            } else {
              // Click different row = expand this one
              onSelectRow(record);
            }
          },
          className: getRowKey(record) === selectedRowKey ? 'table-row-selected' : '',
        })}
        size="middle"
        scroll={{ x: 1100 }}
      />
    </div>
  );
};

export default ReturnTable;
