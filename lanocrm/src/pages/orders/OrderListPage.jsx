import React, { useEffect, useMemo, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import {
  App,
  Input,
  Button,
  Space,
  Tooltip,
  Spin,
} from 'antd';
import {
  SearchOutlined,
  PlusOutlined,
  DownloadOutlined,
  ReloadOutlined,
  MergeCellsOutlined,
} from '@ant-design/icons';
import OrderFilters from '../../components/orders/OrderFilters';
import OrderTable from '../../components/orders/OrderTable';
import OrderSummary from '../../components/orders/OrderSummary';
import OrderDetail from '../../components/orders/OrderDetail';
import {
  fetchOrders,
  fetchOrderDetail,
  setOrderFilters,
  setOrderPage,
} from '../../store/slices/orderSlice';
import { fetchBranches } from '../../store/slices/branchSlice';
import styles from './OrderListPage.module.css';

const OrderListPage = () => {
  const dispatch = useDispatch();
  const { message } = App.useApp();
  const { items, pagination, filters, loading, totals, current, detailLoading } = useSelector((s) => s.orders);
  const { branches } = useSelector((s) => s.branch);

  const [searchText, setSearchText] = useState(filters.search || '');
  const [visibleColumns, setVisibleColumns] = useState(() => [
    'order_number',
    'order_date',
    'customer_name',
    'total',
    'paid_amount',
    'debt_amount',
    'status',
  ]);
  const [selectedId, setSelectedId] = useState(null);

  useEffect(() => {
    dispatch(fetchBranches());
  }, [dispatch]);

  useEffect(() => {
    dispatch(fetchOrders());
  }, [dispatch, filters]);

  useEffect(() => {
    const timer = setTimeout(() => {
      dispatch(setOrderFilters({ search: searchText }));
    }, 400);
    return () => clearTimeout(timer);
  }, [searchText, dispatch]);

  useEffect(() => {
    if (!selectedId) return;
    dispatch(fetchOrderDetail(selectedId));
  }, [selectedId, dispatch]);

  const handleToggleColumn = (key, checked) => {
    setVisibleColumns((prev) => {
      if (checked) return Array.from(new Set([...prev, key]));
      return prev.filter((k) => k !== key);
    });
  };

  const handleExport = () => {
    const headers = visibleColumns.map((c) => c);
    const csv = [headers.join(',')].concat(
      (items || []).map((row) =>
        visibleColumns
          .map((key) => {
            const value = row[key] ?? '';
            return `"${String(value).replace(/"/g, '""')}"`;
          })
          .join(',')
      )
    );
    const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `orders-${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    message.success(`Đã xuất ${items.length} dòng`);
  };

  const onSelectRow = (record) => {
    setSelectedId(record.id);
  };

  const selectedOrder = useMemo(() => {
    if (current && current.id === selectedId) return current;
    return items.find((x) => x.id === selectedId) || null;
  }, [current, selectedId, items]);

  const pageTotals = useMemo(() => {
    return {
      total: items.reduce((sum, r) => sum + Number(r.total || 0), 0),
      paid: items.reduce((sum, r) => sum + Number(r.paid_amount || 0), 0),
      debt: items.reduce((sum, r) => sum + Number(r.debt_amount || 0), 0),
    };
  }, [items]);

  return (
    <div className={styles.page}>
      <div className={styles.headerRow}>
        <Input
          allowClear
          prefix={<SearchOutlined />}
          placeholder="Theo mã phiếu đặt"
          value={searchText}
          onChange={(e) => setSearchText(e.target.value)}
          className={styles.search}
        />
        <Space>
          <Tooltip title="Đặt hàng">
            <Button type="primary" icon={<PlusOutlined />}>Đặt hàng</Button>
          </Tooltip>
          <Tooltip title="Gộp đơn (coming soon)">
            <Button icon={<MergeCellsOutlined />} disabled>Gộp đơn</Button>
          </Tooltip>
          <Button icon={<DownloadOutlined />} onClick={handleExport}>Xuất file</Button>
          <Button icon={<ReloadOutlined />} onClick={() => dispatch(fetchOrders(filters))} />
        </Space>
      </div>

      <OrderSummary totals={totals} />

      <div className={styles.layout}>
        <OrderFilters
          filters={filters}
          onChange={(payload) => dispatch(setOrderFilters(payload))}
          branches={branches}
        />

        <div className={styles.tableArea}>
          <OrderTable
            data={items}
            loading={loading}
            pagination={pagination}
            onPageChange={(pageInfo) => {
              dispatch(setOrderPage(pageInfo));
              dispatch(fetchOrders({ ...filters, ...pageInfo }));
            }}
            onSelectRow={onSelectRow}
            selectedRowKey={selectedId}
            visibleColumns={visibleColumns}
            onToggleColumn={handleToggleColumn}
          />
          <div className={styles.pageTotals}>
            <span>Tổng trang: </span>
            <Space size="large">
              <span>Khách cần trả: {pageTotals.total.toLocaleString('vi-VN')} đ</span>
              <span>Khách đã trả: {pageTotals.paid.toLocaleString('vi-VN')} đ</span>
              <span>Còn nợ: {pageTotals.debt.toLocaleString('vi-VN')} đ</span>
            </Space>
          </div>

          <Spin spinning={detailLoading}>
            <OrderDetail order={selectedOrder} />
          </Spin>
        </div>
      </div>
    </div>
  );
};

export default OrderListPage;
