import React, { useEffect, useMemo, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import {
  App,
  Input,
  Button,
  Space,
  Tooltip,
  Popover,
  Checkbox,
  Row,
  Col,
} from 'antd';
import {
  SearchOutlined,
  FilterOutlined,
  DownloadOutlined,
  MenuOutlined,
  SettingOutlined,
  QuestionCircleOutlined,
} from '@ant-design/icons';
import ShipmentFilters from '../../components/shipments/ShipmentFilters';
import ShipmentTable, { columnCatalog } from '../../components/shipments/ShipmentTable';
import styles from './ShipmentListPage.module.css';
import {
  fetchShipments,
  setShipmentFilters,
  setShipmentPage,
} from '../../store/slices/shipmentSlice';
import { fetchBranches } from '../../store/slices/branchSlice';
import { DEFAULT_SHIPMENT_COLUMNS } from '../../constants/shipments';

const STORAGE_KEY = 'lano_shipment_visible_columns';

const ShipmentListPage = () => {
  const dispatch = useDispatch();
  const { message } = App.useApp();
  const { items, pagination, filters, loading, summary } = useSelector((s) => s.shipments);
  const { branches } = useSelector((s) => s.branch);

  const [searchText, setSearchText] = useState(filters.search || '');
  const [visibleColumns, setVisibleColumns] = useState(() => {
    const stored = localStorage.getItem(STORAGE_KEY);
    return stored ? JSON.parse(stored) : DEFAULT_SHIPMENT_COLUMNS;
  });
  const [columnPopoverOpen, setColumnPopoverOpen] = useState(false);
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

  return (
    <div className={styles.page}>
      <div className={styles.headerRow}>
        <Input
          allowClear
          prefix={<SearchOutlined />}
          suffix={<FilterOutlined style={{ cursor: 'pointer', color: '#1890ff' }} />}
          placeholder="Theo mã vận đơn"
          value={searchText}
          onChange={(e) => setSearchText(e.target.value)}
          className={styles.search}
        />
        <Space>
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
        <ShipmentFilters
          filters={filters}
          onChange={handleFiltersChange}
          branches={branches}
        />

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
      </div>
    </div>
  );
};

export default ShipmentListPage;
