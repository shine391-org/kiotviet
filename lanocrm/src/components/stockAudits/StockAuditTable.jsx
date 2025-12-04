import React, { useMemo, useState } from 'react';
import { Table, Tag, Dropdown, Checkbox, Button, Typography, Empty } from 'antd';
import { StarFilled, StarOutlined, DownOutlined, InboxOutlined } from '@ant-design/icons';
import {
  DEFAULT_STOCK_AUDIT_COLUMNS,
  STOCK_AUDIT_STATUSES,
  STOCK_AUDIT_COLUMN_LABELS,
  formatAuditDateTime,
  formatAuditNumber,
  formatAuditCurrency,
} from '../../constants/stockAudits';
import styles from './StockAuditTable.module.css';

const statusMap = STOCK_AUDIT_STATUSES.reduce((acc, s) => ({ ...acc, [s.value]: s }), {});

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
    title: 'Mã kiểm kho',
    dataIndex: 'code',
    sorter: true,
    width: 160,
    render: (v) => <Typography.Link>{v}</Typography.Link>,
  },
  createdTime: {
    title: 'Thời gian',
    dataIndex: 'createdTime',
    sorter: true,
    width: 170,
    render: (v) => formatAuditDateTime(v),
  },
  creatorName: { title: 'Người tạo', dataIndex: 'creatorName', width: 160 },
  reconciledBy: { title: 'Người cân bằng', dataIndex: 'reconciledBy', width: 170, render: (v) => v || '—' },
  reconciledDate: {
    title: 'Ngày cân bằng',
    dataIndex: 'reconciledDate',
    sorter: true,
    width: 170,
    render: (v) => formatAuditDateTime(v),
  },
  actualQuantity: {
    title: 'SL thực tế',
    dataIndex: 'actualQuantity',
    align: 'right',
    width: 130,
    render: (v) => formatAuditNumber(Number(v || 0)),
  },
  totalActualValue: {
    title: 'Tổng thực tế',
    dataIndex: 'totalActualValue',
    align: 'right',
    width: 150,
    render: (v) => formatAuditCurrency(Number(v || 0)),
  },
  differenceQuantity: {
    title: 'Tổng chênh lệch',
    dataIndex: 'differenceQuantity',
    align: 'right',
    width: 150,
    render: (v) => formatAuditNumber(Number(v || 0)),
  },
  differenceValue: {
    title: 'Tổng giá trị lệch',
    dataIndex: 'differenceValue',
    align: 'right',
    width: 170,
    render: (v) => formatAuditCurrency(Number(v || 0)),
  },
  overCountedQty: {
    title: 'SL lệch tăng',
    dataIndex: 'overCountedQty',
    align: 'right',
    width: 130,
    render: (v) => formatAuditNumber(Number(v || 0)),
  },
  overCountedValue: {
    title: 'Tổng giá trị tăng',
    dataIndex: 'overCountedValue',
    align: 'right',
    width: 160,
    render: (v) => formatAuditCurrency(Number(v || 0)),
  },
  underCountedQty: {
    title: 'SL lệch giảm',
    dataIndex: 'underCountedQty',
    align: 'right',
    width: 130,
    render: (v) => formatAuditNumber(Number(v || 0)),
  },
  underCountedValue: {
    title: 'Tổng giá trị giảm',
    dataIndex: 'underCountedValue',
    align: 'right',
    width: 170,
    render: (v) => formatAuditCurrency(Number(v || 0)),
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

const StockAuditTable = ({
  data,
  loading,
  pagination,
  onPageChange,
  visibleColumns,
  onToggleColumn,
}) => {
  const [selectedRowKeys, setSelectedRowKeys] = useState([]);
  const [favorites, setFavorites] = useState([]);

  const columns = useMemo(() => {
    const ctx = { favorites, setFavorites };
    return (visibleColumns || DEFAULT_STOCK_AUDIT_COLUMNS)
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

  const checkedColumns = visibleColumns && visibleColumns.length ? visibleColumns : DEFAULT_STOCK_AUDIT_COLUMNS;

  const columnMenu = {
    items: Object.keys(columnCatalog).map((key) => ({
      key,
      label: (
        <Checkbox
          checked={checkedColumns.includes(key)}
          onChange={(e) => onToggleColumn(key, e.target.checked)}
        >
          {STOCK_AUDIT_COLUMN_LABELS[key] || columnCatalog[key].title || '—'}
        </Checkbox>
      ),
    })),
  };

  return (
    <div className={styles.wrapper}>
      <div className={styles.tableHeader}>
        <Typography.Text strong>Danh sách phiếu kiểm kho</Typography.Text>
        <Dropdown menu={columnMenu} trigger={['click']}>
          <Button size="small">
            Ẩn/hiện cột <DownOutlined />
          </Button>
        </Dropdown>
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
          pageSizeOptions: [15, 25, 50, 100],
          showTotal: (total, range) => `${range[0]} - ${range[1]} trong ${total} kiểm kho`,
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
        locale={{
          emptyText: (
            <Empty
              image={<InboxOutlined style={{ fontSize: 32 }} />}
              description={
                <div className={styles.emptyDesc}>
                  <div>Không tìm thấy kết quả</div>
                  <Typography.Text type="secondary">
                    Không tìm thấy giao dịch nào phù hợp trong tháng này.
                  </Typography.Text>
                  <Typography.Link onClick={() => onPageChange?.({ page: 1 })}>
                    Nhấn vào đây để tiếp tục tìm kiếm
                  </Typography.Link>
                </div>
              }
            />
          ),
        }}
        size="middle"
        scroll={{ x: 1300 }}
      />
    </div>
  );
};

export default StockAuditTable;
