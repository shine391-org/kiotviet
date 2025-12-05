import React, { useMemo, useState } from 'react';
import { Table, Tag, Dropdown, Checkbox, Button, Space, Typography } from 'antd';
import { StarFilled, StarOutlined, DownOutlined } from '@ant-design/icons';
import {
  DEFAULT_TRANSFER_COLUMNS,
  TRANSFER_STATUSES,
  formatDateTime,
  formatNumber,
  formatCurrency,
} from '../../constants/transfers';
import styles from './TransferTable.module.css';

const statusMap = TRANSFER_STATUSES.reduce((acc, s) => ({ ...acc, [s.value]: s }), {});

const columnCatalog = {
  favorite: {
    title: '',
    dataIndex: 'favorite',
    width: 60,
    render: (_, record, __, { favorites, setFavorites }) => {
      const active = favorites.includes(record.code);
      const toggle = (e) => {
        e.stopPropagation();
        setFavorites((prev) =>
          prev.includes(record.code) ? prev.filter((c) => c !== record.code) : [...prev, record.code]
        );
      };
      return (
        <Button
          type="text"
          size="small"
          icon={active ? <StarFilled style={{ color: '#f5a524' }} /> : <StarOutlined />}
          onClick={toggle}
        />
      );
    },
  },
  code: {
    title: 'Mã chuyển hàng',
    dataIndex: 'code',
    sorter: true,
    render: (v) => <Typography.Link>{v}</Typography.Link>,
    width: 170,
  },
  creatorName: { title: 'Người tạo', dataIndex: 'creatorName', width: 150 },
  receiverName: { title: 'Người nhận', dataIndex: 'receiverName', width: 150 },
  transferDate: {
    title: 'Ngày chuyển',
    dataIndex: 'transferDate',
    sorter: true,
    width: 160,
    render: (v) => formatDateTime(v),
  },
  receiveDate: {
    title: 'Ngày nhận',
    dataIndex: 'receiveDate',
    sorter: true,
    width: 160,
    render: (v) => formatDateTime(v),
  },
  createdAt: {
    title: 'Thời gian tạo',
    dataIndex: 'createdAt',
    sorter: true,
    width: 160,
    render: (v) => formatDateTime(v),
  },
  fromBranch: { title: 'Từ chi nhánh', dataIndex: 'fromBranch', width: 160 },
  toBranch: { title: 'Tới chi nhánh', dataIndex: 'toBranch', width: 160 },
  quantitySent: {
    title: 'Tổng SL chuyển',
    dataIndex: 'quantitySent',
    sorter: true,
    width: 130,
    align: 'right',
    render: (v) => formatNumber(Number(v || 0)),
  },
  valueSent: {
    title: 'Giá trị chuyển',
    dataIndex: 'valueSent',
    sorter: true,
    width: 150,
    align: 'right',
    render: (v) => formatCurrency(Number(v || 0)),
  },
  quantityReceived: {
    title: 'Tổng SL nhận',
    dataIndex: 'quantityReceived',
    sorter: true,
    width: 130,
    align: 'right',
    render: (v) => formatNumber(Number(v || 0)),
  },
  valueReceived: {
    title: 'Giá trị nhận',
    dataIndex: 'valueReceived',
    sorter: true,
    width: 150,
    align: 'right',
    render: (v) => formatCurrency(Number(v || 0)),
  },
  totalItems: {
    title: 'Tổng số mặt hàng',
    dataIndex: 'totalItems',
    width: 150,
    align: 'right',
    render: (v) => formatNumber(Number(v || 0)),
  },
  notes: {
    title: 'Ghi chú',
    dataIndex: 'notes',
    ellipsis: true,
    render: (v) => v || '—',
  },
  status: {
    title: 'Trạng thái',
    dataIndex: 'status',
    width: 140,
    render: (v) => {
      const meta = statusMap[v] || {};
      return <Tag color={meta.color === 'default' ? undefined : meta.color}>{meta.label || v}</Tag>;
    },
  },
};

const TransferTable = ({
  data,
  loading,
  pagination,
  onPageChange,
  onExpandRow,
  expandedRowKeys,
  visibleColumns,
  onToggleColumn,
  summary,
  renderDetail,
}) => {
  const [selectedRowKeys, setSelectedRowKeys] = useState([]);
  const [favorites, setFavorites] = useState([]);

  const columns = useMemo(() => {
    const ctx = { favorites, setFavorites };
    return (visibleColumns || DEFAULT_TRANSFER_COLUMNS)
      .map((key) => {
        const col = columnCatalog[key];
        if (!col) return null;
        if (col.render && col.dataIndex === 'favorite') {
          return {
            ...col,
            key,
            render: (v, r, i) => col.render(v, r, i, ctx),
          };
        }
        return { ...col, key };
      })
      .filter(Boolean);
  }, [visibleColumns, favorites]);

  const columnMenu = {
    items: Object.keys(columnCatalog).map((key) => ({
      key,
      label: (
        <Checkbox
          checked={visibleColumns.includes(key)}
          onChange={(e) => onToggleColumn(key, e.target.checked)}
        >
          {columnCatalog[key].title || '—'}
        </Checkbox>
      ),
    })),
  };

  const summaryRow = summary || {};

  return (
    <div className={styles.wrapper}>
      <div className={styles.tableHeader}>
        <Space>
          <Typography.Text strong>Danh sách phiếu chuyển</Typography.Text>
        </Space>
        <Dropdown menu={columnMenu} trigger={['click']}>
          <Button size="small">
            Ẩn/hiện cột <DownOutlined />
          </Button>
        </Dropdown>
      </div>

      <div className={styles.summaryBar}>
        <Space size={18} wrap>
          <span>
            Tổng SL chuyển: <strong>{formatNumber(summaryRow.totalQtySent || 0)}</strong>
          </span>
          <span>
            Giá trị chuyển: <strong>{formatCurrency(summaryRow.totalValueSent || 0)}</strong>
          </span>
          <span>
            Tổng SL nhận: <strong>{formatNumber(summaryRow.totalQtyReceived || 0)}</strong>
          </span>
          <span>
            Giá trị nhận: <strong>{formatCurrency(summaryRow.totalValueReceived || 0)}</strong>
          </span>
          <span>
            Tổng mặt hàng: <strong>{formatNumber(summaryRow.totalItems || 0)}</strong>
          </span>
        </Space>
      </div>

      <Table
        rowKey="code"
        dataSource={data}
        columns={columns}
        loading={loading}
        pagination={{
          current: pagination?.page || 1,
          pageSize: pagination?.limit || 15,
          total: pagination?.total || 0,
          showSizeChanger: true,
          showTotal: (total) =>
            `${pagination?.page || 1} - ${Math.min(
              (pagination?.page || 1) * (pagination?.limit || 15),
              total
            )} trong ${total} giao dịch`,
        }}
        onChange={(pager, _filters, sorter) => {
          const sortField = sorter?.field || sorter?.columnKey;
          const sortOrder = sorter?.order ? (sorter.order === 'ascend' ? 'asc' : 'desc') : undefined;
          onPageChange?.({
            page: pager.current,
            limit: pager.pageSize,
            sort: sortField ? `${sortField},${sortOrder || 'desc'}` : undefined,
          });
        }}
        rowSelection={{
          selectedRowKeys,
          onChange: (keys) => setSelectedRowKeys(keys),
        }}
        expandable={{
          expandedRowKeys,
          expandRowByClick: true,
          onExpand: (expanded, record) => onExpandRow?.(expanded, record),
          expandedRowRender: (record) => renderDetail?.(record),
        }}
        size="middle"
        scroll={{ x: 1200 }}
      />
    </div>
  );
};

export default TransferTable;
