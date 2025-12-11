import React, { useEffect, useMemo, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import {
  App,
  Input,
  Button,
  Space,
  Tooltip,
  Spin,
  Popover,
  Checkbox,
  Row,
  Col,
  Dropdown,
} from 'antd';
import {
  SearchOutlined,
  FilterOutlined,
  PlusOutlined,
  DownloadOutlined,
  MenuOutlined,
  SettingOutlined,
  QuestionCircleOutlined,
  MergeCellsOutlined,
} from '@ant-design/icons';
import OrderFilters from '../../components/orders/OrderFilters';
import OrderTable, { columnCatalog } from '../../components/orders/OrderTable';
import OrderSummary from '../../components/orders/OrderSummary';
import OrderDetail from '../../components/orders/OrderDetail';
import {
  fetchOrders,
  fetchOrderDetail,
  setOrderFilters,
  setOrderPage,
} from '../../store/slices/orderSlice';
import { fetchBranches } from '../../store/slices/branchSlice';
import MergeOrdersModal from '../../components/orders/MergeOrdersModal';
import styles from './OrderListPage.module.css';

const OrderListPage = () => {
  const dispatch = useDispatch();
  const { message } = App.useApp();
  const { items, pagination, filters, loading, totals, current, detailLoading } = useSelector((s) => s.orders);
  const { branches } = useSelector((s) => s.branch);

  const [searchText, setSearchText] = useState(filters.search || '');
  const [visibleColumns, setVisibleColumns] = useState(() => [
    'tracking_code',
    'order_number',
    'invoice_code',
    'order_date',
    'created_at',
    'updated_at',
    'delivery_date',
    'waiting_days',
  ]);
  const [selectedId, setSelectedId] = useState(null);
  const [columnPopoverOpen, setColumnPopoverOpen] = useState(false);
  const [mergeModalOpen, setMergeModalOpen] = useState(false);
  const [mergeLoading, setMergeLoading] = useState(false);
  const [searchPopoverOpen, setSearchPopoverOpen] = useState(false);
  const [productSearch, setProductSearch] = useState('');
  const [customerSearch, setCustomerSearch] = useState('');

  // Compute mergeable orders: same customer or phone within 7 days
  const mergeableOrders = useMemo(() => {
    if (!mergeModalOpen || !items || items.length === 0) return [];

    const now = new Date();
    const sevenDaysAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);

    // Get orders within last 7 days
    const recentOrders = items.filter((order) => {
      const orderDate = new Date(order.order_date || order.created_at);
      return orderDate >= sevenDaysAgo;
    });

    // Group by customer_id or customer_phone
    const groups = {};
    recentOrders.forEach((order) => {
      const key = order.customer_id
        ? `customer_${order.customer_id}`
        : order.customer_phone
          ? `phone_${order.customer_phone}`
          : null;

      if (key) {
        if (!groups[key]) {
          groups[key] = [];
        }
        groups[key].push(order);
      }
    });

    // Return orders that have at least 2 orders with same customer/phone (mergeable)
    const mergeable = [];
    Object.values(groups).forEach((groupOrders) => {
      if (groupOrders.length >= 2) {
        mergeable.push(...groupOrders);
      }
    });

    return mergeable;
  }, [mergeModalOpen, items]);

  // Search filter popover content
  const searchPopoverContent = (
    <div style={{ width: 320, padding: 8 }}>
      <div style={{ marginBottom: 12 }}>
        <Input
          placeholder="Theo mã phiếu đặt"
          value={searchText}
          onChange={(e) => setSearchText(e.target.value)}
          style={{ marginBottom: 8 }}
        />
      </div>
      <div style={{ marginBottom: 12 }}>
        <Input
          placeholder="Theo mã, tên hàng"
          value={productSearch}
          onChange={(e) => setProductSearch(e.target.value)}
          style={{ marginBottom: 8 }}
        />
      </div>
      <div style={{ marginBottom: 12 }}>
        <Input
          placeholder="Theo mã, tên, số điện thoại khách hàng"
          value={customerSearch}
          onChange={(e) => setCustomerSearch(e.target.value)}
        />
      </div>
      <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8 }}>
        <Button onClick={() => setSearchPopoverOpen(false)}>Mở rộng</Button>
        <Button type="primary" onClick={() => {
          dispatch(setOrderFilters({
            search: searchText,
            product_search: productSearch,
            customer_search: customerSearch
          }));
          setSearchPopoverOpen(false);
        }}>Tìm kiếm</Button>
      </div>
    </div>
  );

  // Column popover content for burger menu
  const columnKeys = Object.keys(columnCatalog);
  const halfLength = Math.ceil(columnKeys.length / 2);
  const leftColumnKeys = columnKeys.slice(0, halfLength);
  const rightColumnKeys = columnKeys.slice(halfLength);

  const columnPopoverContent = (
    <div style={{ width: 420, padding: 8 }}>
      <Row gutter={16}>
        <Col span={12}>
          {leftColumnKeys.map((key) => (
            <div key={key} style={{ marginBottom: 8 }}>
              <Checkbox
                checked={visibleColumns.includes(key)}
                onChange={(e) => handleToggleColumn(key, e.target.checked)}
              >
                {columnCatalog[key].title}
              </Checkbox>
            </div>
          ))}
        </Col>
        <Col span={12}>
          {rightColumnKeys.map((key) => (
            <div key={key} style={{ marginBottom: 8 }}>
              <Checkbox
                checked={visibleColumns.includes(key)}
                onChange={(e) => handleToggleColumn(key, e.target.checked)}
              >
                {columnCatalog[key].title}
              </Checkbox>
            </div>
          ))}
        </Col>
      </Row>
    </div>
  );

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
        <Popover
          content={searchPopoverContent}
          trigger="click"
          open={searchPopoverOpen}
          onOpenChange={setSearchPopoverOpen}
          placement="bottomLeft"
        >
          <Input
            allowClear
            prefix={<SearchOutlined />}
            suffix={<FilterOutlined style={{ cursor: 'pointer', color: '#1890ff' }} />}
            placeholder="Theo mã phiếu đặt"
            value={searchText}
            onChange={(e) => setSearchText(e.target.value)}
            className={styles.search}
            onClick={() => setSearchPopoverOpen(true)}
          />
        </Popover>
        <Space>
          <Button type="primary" icon={<PlusOutlined />}>Đặt hàng</Button>
          <Button icon={<MergeCellsOutlined />} onClick={() => setMergeModalOpen(true)}>Gộp đơn</Button>
          <Button icon={<DownloadOutlined />} onClick={handleExport}>Xuất file</Button>
          <Popover
            content={columnPopoverContent}
            trigger="click"
            open={columnPopoverOpen}
            onOpenChange={setColumnPopoverOpen}
            placement="bottomRight"
            title="Chọn cột hiển thị"
          >
            <Tooltip title="Tùy chọn hiển thị">
              <Button icon={<MenuOutlined />} />
            </Tooltip>
          </Popover>
          <Tooltip title="Cài đặt">
            <Button icon={<SettingOutlined />} />
          </Tooltip>
          <Tooltip title="Trợ giúp">
            <Button icon={<QuestionCircleOutlined />} />
          </Tooltip>
        </Space>
      </div>

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
          />
          <div className={styles.pageTotals}>
            <span>Tổng trang: </span>
            <Space size="large">
              <span>Khách cần trả: {pageTotals.total.toLocaleString('vi-VN')} đ</span>
              <span>Khách đã trả: {pageTotals.paid.toLocaleString('vi-VN')} đ</span>
              <span>Còn nợ: {pageTotals.debt.toLocaleString('vi-VN')} đ</span>
            </Space>
          </div>
        </div>
      </div>

      {/* Merge Orders Modal */}
      <MergeOrdersModal
        open={mergeModalOpen}
        onClose={() => setMergeModalOpen(false)}
        orders={mergeableOrders}
        loading={mergeLoading || loading}
      />
    </div>
  );
};

export default OrderListPage;
