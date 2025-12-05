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
  FilterOutlined,
  DownloadOutlined,
  ReloadOutlined,
} from '@ant-design/icons';
import ShipmentFilters from '../../components/shipments/ShipmentFilters';
import ShipmentTable from '../../components/shipments/ShipmentTable';
import ShipmentDetail from '../../components/shipments/ShipmentDetail';
import styles from './ShipmentListPage.module.css';
import {
  fetchShipments,
  fetchShipmentDetail,
  setShipmentFilters,
  setShipmentPage,
} from '../../store/slices/shipmentSlice';
import { fetchBranches } from '../../store/slices/branchSlice';
import { DEFAULT_SHIPMENT_COLUMNS } from '../../constants/shipments';

const STORAGE_KEY = 'lano_shipment_visible_columns';

const ShipmentListPage = () => {
  const dispatch = useDispatch();
  const { message } = App.useApp();
  const { items, pagination, filters, loading, summary, current, detailLoading } = useSelector((s) => s.shipments);
  const { branches } = useSelector((s) => s.branch);

  const [searchText, setSearchText] = useState(filters.search || '');
  const [visibleColumns, setVisibleColumns] = useState(() => {
    const stored = localStorage.getItem(STORAGE_KEY);
    return stored ? JSON.parse(stored) : DEFAULT_SHIPMENT_COLUMNS;
  });
  const [showFilters, setShowFilters] = useState(true);
  const [selectedId, setSelectedId] = useState(null);

  useEffect(() => {
    dispatch(fetchBranches());
  }, [dispatch]);

  useEffect(() => {
    dispatch(fetchShipments());
  }, [dispatch, filters]);

  useEffect(() => {
    const timer = setTimeout(() => {
      dispatch(setShipmentFilters({ search: searchText }));
    }, 400);
    return () => clearTimeout(timer);
  }, [searchText, dispatch]);

  useEffect(() => {
    if (!selectedId) return;
    dispatch(fetchShipmentDetail(selectedId));
  }, [selectedId, dispatch]);

  useEffect(() => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(visibleColumns));
  }, [visibleColumns]);

  const handleToggleColumn = (key, checked) => {
    setVisibleColumns((prev) => {
      if (checked) return Array.from(new Set([...prev, key]));
      return prev.filter((c) => c !== key);
    });
  };

  const handleExport = () => {
    const headers = visibleColumns.map((key) => key);
    const csv = [headers.join(',')].concat(
      (items || []).map((row) =>
        visibleColumns
          .map((key) => `"${String(row[key] ?? row[`${key}_name`] ?? '').replace(/"/g, '""')}"`)
          .join(',')
      )
    );
    const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `shipments-${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    message.success(`Đã xuất ${items.length} vận đơn`);
  };

  const onSelectRow = (record) => {
    setSelectedId(record.id);
  };

  const selectedShipment = useMemo(() => {
    if (current && current.id === selectedId) return current;
    return items.find((x) => x.id === selectedId) || null;
  }, [current, selectedId, items]);

  const handleFiltersChange = (payload) => {
    dispatch(setShipmentFilters(payload));
  };

  const handlePageChange = ({ page, limit, sort }) => {
    if (sort) {
      dispatch(setShipmentFilters({ sort }));
      dispatch(fetchShipments({ sort, page: 1, limit }));
      return;
    }
    dispatch(setShipmentPage({ page, limit }));
    dispatch(fetchShipments({ page, limit }));
  };

  const layoutClass = `${styles.layout} ${selectedShipment ? styles.withDetail : ''}`;

  return (
    <div className={styles.page}>
      <div className={styles.headerRow}>
        <Input
          allowClear
          prefix={<SearchOutlined />}
          placeholder="Theo mã vận đơn"
          value={searchText}
          onChange={(e) => setSearchText(e.target.value)}
          className={styles.search}
        />
        <Space>
          <Tooltip title="Bật/tắt bộ lọc">
            <Button icon={<FilterOutlined />} onClick={() => setShowFilters((v) => !v)} />
          </Tooltip>
          <Button icon={<DownloadOutlined />} onClick={handleExport}>Xuất file</Button>
          <Button icon={<ReloadOutlined />} onClick={() => dispatch(fetchShipments(filters))} />
        </Space>
      </div>

      <div className={styles.summaryBar}>
        Tổng COD: {(summary?.cod_total || 0).toLocaleString('vi-VN')} đ
      </div>

      <div className={layoutClass}>
        {showFilters && (
          <ShipmentFilters
            filters={filters}
            onChange={handleFiltersChange}
            branches={branches}
          />
        )}

        <div className={styles.tableArea}>
          <ShipmentTable
            data={items}
            loading={loading}
            pagination={pagination}
            onPageChange={handlePageChange}
            onSelectRow={onSelectRow}
            selectedRowKey={selectedId}
            visibleColumns={visibleColumns}
            onToggleColumn={handleToggleColumn}
            summary={summary}
          />
        </div>

        {selectedShipment && (
          <div className={styles.detailDrawer}>
            <Spin spinning={detailLoading}>
              <ShipmentDetail
                shipment={selectedShipment}
                loading={detailLoading}
                onClose={() => setSelectedId(null)}
              />
            </Spin>
          </div>
        )}
      </div>
    </div>
  );
};

export default ShipmentListPage;
