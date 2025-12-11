import React, { useMemo, useState, useCallback } from 'react';
import { Table, Tag, Dropdown, Checkbox, Button, Typography } from 'antd';
import { StarOutlined, StarFilled } from '@ant-design/icons';
import dayjs from 'dayjs';
import { formatCurrency, formatDate } from '../../utils/formatters';
import { formatTransactionCode } from '../../constants/cash';
import CashDetailPanel from './CashDetailPanel';
import styles from '../../pages/cash/CashBookPage.module.css';

const statusColors = {
  approved: 'green',
  pending: 'orange',
  cancelled: 'red',
};

const CashTable = ({
  data,
  loading,
  pagination,
  onPageChange,
  onDelete,
  onRefresh,
  onExport,
  branchesMap = {},
  summaryLimited,
  onEdit,
  onPrint,
}) => {
  const [selectedRowKeys, setSelectedRowKeys] = useState([]);
  const [expandedRowKey, setExpandedRowKey] = useState(null);
  const [favorites, setFavorites] = useState([]);
  const [visibleCols, setVisibleCols] = useState({
    favorite: true,
    code: true,
    transaction_date: true,
    category: true,
    payer_name: true,
    amount: true,
  });

  const toggleColumn = useCallback((key, checked) => {
    setVisibleCols((prev) => ({ ...prev, [key]: checked }));
  }, []);

  const toggleFavorite = useCallback((id) => {
    setFavorites((prev) =>
      prev.includes(id) ? prev.filter((f) => f !== id) : [...prev, id]
    );
  }, []);

  // Handle row click - toggle expand/collapse
  const handleRowClick = useCallback((record) => {
    const key = record.id;
    if (expandedRowKey === key) {
      // Click same row = collapse
      setExpandedRowKey(null);
    } else {
      // Click different row = expand this one
      setExpandedRowKey(key);
    }
  }, [expandedRowKey]);

  const baseColumns = useMemo(() => [
    {
      key: 'favorite',
      title: '',
      dataIndex: 'id',
      width: 40,
      render: (id) => (
        <span
          onClick={(e) => { e.stopPropagation(); toggleFavorite(id); }}
          style={{ cursor: 'pointer' }}
        >
          {favorites.includes(id) ? (
            <StarFilled style={{ color: '#faad14' }} />
          ) : (
            <StarOutlined style={{ color: '#d9d9d9' }} />
          )}
        </span>
      ),
    },
    {
      key: 'code',
      title: 'Mã phiếu',
      dataIndex: 'id',
      render: (_, record) => (
        <Typography.Link style={{ fontWeight: 500 }}>
          {formatTransactionCode(record)}
        </Typography.Link>
      ),
    },
    {
      key: 'transaction_date',
      title: 'Thời gian',
      dataIndex: 'transaction_date',
      render: (value) => (value ? dayjs(value).format('DD/MM/YYYY HH:mm') : '—'),
    },
    {
      key: 'category',
      title: 'Loại thu chi',
      dataIndex: 'category',
      render: (v, record) => v || (record.type === 'RECEIPT' ? 'Thu Tiền khách trả' : 'Chi trả nhà cung cấp'),
    },
    {
      key: 'payer_name',
      title: 'Người nộp/nhận',
      dataIndex: 'payer_name',
      render: (v) => v || '—',
    },
    {
      key: 'amount',
      title: 'Giá trị',
      dataIndex: 'amount',
      align: 'right',
      render: (_, record) => (
        <span className={record.type === 'RECEIPT' ? styles.amountReceipt : styles.amountPayment}>
          {formatCurrency(record.amount)}
        </span>
      ),
    },
  ], [favorites, toggleFavorite]);

  const columns = useMemo(
    () => baseColumns.filter((col) => visibleCols[col.key]),
    [baseColumns, visibleCols]
  );

  const columnMenuItems = useMemo(() => (
    Object.keys(visibleCols).map((key) => {
      const title = baseColumns.find((c) => c.key === key)?.title || key;
      return {
        key,
        label: (
          <Checkbox
            checked={visibleCols[key]}
            onChange={(e) => toggleColumn(key, e.target.checked)}
          >
            {title || key}
          </Checkbox>
        ),
      };
    })
  ), [visibleCols, baseColumns, toggleColumn]);

  // Render expanded row with CashDetailPanel
  const expandedRowRender = (record) => (
    <CashDetailPanel
      record={record}
      branchesMap={branchesMap}
      onEdit={onEdit}
      onDelete={onDelete}
      onPrint={onPrint}
    />
  );

  return (
    <div className={styles.tableCard}>
      <Table
        size="middle"
        rowKey="id"
        dataSource={data}
        columns={columns}
        loading={loading}
        expandable={{
          expandedRowKeys: expandedRowKey ? [expandedRowKey] : [],
          expandedRowRender,
          expandIcon: () => null, // Hide default expand icon
          expandRowByClick: false, // We handle click ourselves
        }}
        rowSelection={{
          selectedRowKeys,
          onChange: setSelectedRowKeys,
        }}
        onRow={(record) => ({
          onClick: () => handleRowClick(record),
          className: expandedRowKey === record.id ? styles.selectedRow : '',
        })}
        pagination={{
          current: pagination.page,
          pageSize: pagination.limit,
          total: pagination.total,
          showSizeChanger: true,
          pageSizeOptions: ['10', '20', '50', '100'],
        }}
        onChange={(paginationInfo) =>
          onPageChange({
            page: paginationInfo.current,
            limit: paginationInfo.pageSize,
          })
        }
      />
    </div>
  );
};

export default CashTable;
