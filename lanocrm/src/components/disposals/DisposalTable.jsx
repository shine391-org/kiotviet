import React, { useMemo } from 'react';
import { Table, Tag, Dropdown, Checkbox, Button, Typography } from 'antd';
import { DownOutlined } from '@ant-design/icons';
import { DISPOSAL_STATUSES, formatDisposalDate } from '../../constants/disposals';

const statusMap = DISPOSAL_STATUSES.reduce((acc, s) => ({ ...acc, [s.value]: s }), {});

export const disposalColumnCatalog = {
  dispose_code: { title: 'Mã xuất hủy', dataIndex: 'dispose_code' },
  total_value: {
    title: 'Tổng giá trị hủy',
    dataIndex: 'total_value',
    align: 'right',
    render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
    width: 160,
  },
  disposed_at: { title: 'Thời gian', dataIndex: 'disposed_at', width: 160, render: formatDisposalDate },
  branch_name: { title: 'Chi nhánh', dataIndex: 'branch_name' },
  creator_name: { title: 'Người tạo', dataIndex: 'creator_name' },
  executor_name: { title: 'Người xuất hủy', dataIndex: 'executor_name' },
  notes: { title: 'Ghi chú', dataIndex: 'notes', ellipsis: true },
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

const DisposalTable = ({
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
        .map((key) => ({ key, ...disposalColumnCatalog[key] }))
        .filter(Boolean),
    [visibleColumns]
  );

  const columnMenuItems = Object.keys(disposalColumnCatalog).map((key) => ({
    key,
    label: (
      <Checkbox
        checked={visibleColumns.includes(key)}
        onChange={(e) => onToggleColumn(key, e.target.checked)}
      >
        {disposalColumnCatalog[key]?.title || key}
      </Checkbox>
    ),
  }));

  const menu = { items: columnMenuItems };

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8 }}>
        <Typography.Text strong>Danh sách phiếu xuất hủy</Typography.Text>
        <Dropdown menu={menu} trigger={['click']}>
          <Button size="small">
            Cột hiển thị <DownOutlined />
          </Button>
        </Dropdown>
      </div>
      <Table
        rowKey={(record) => record.id || record.dispose_code}
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
        rowClassName={(record) =>
          record.id === selectedRowKey || record.dispose_code === selectedRowKey ? 'table-row-selected' : ''
        }
        size="middle"
      />
    </div>
  );
};

export default DisposalTable;
