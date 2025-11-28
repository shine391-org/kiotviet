import React, { useEffect, useMemo, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import {
  App,
  Input,
  Button,
  Space,
  Tooltip,
  Dropdown,
  Checkbox,
  Spin,
} from 'antd';
import {
  SearchOutlined,
  PlusOutlined,
  DownloadOutlined,
  ReloadOutlined,
  ColumnHeightOutlined,
} from '@ant-design/icons';
import DisposalFilters from '../../components/disposals/DisposalFilters';
import DisposalTable, { disposalColumnCatalog } from '../../components/disposals/DisposalTable';
import DisposalDetail from '../../components/disposals/DisposalDetail';
import DisposalSummary from '../../components/disposals/DisposalSummary';
import {
  fetchDisposals,
  fetchDisposalDetail,
  setDisposalFilters,
  setDisposalPage,
} from '../../store/slices/disposalSlice';
import { fetchBranches } from '../../store/slices/branchSlice';
import { DEFAULT_DISPOSAL_COLUMNS } from '../../constants/disposals';
import styles from './DisposalListPage.module.css';

const DisposalListPage = () => {
  const dispatch = useDispatch();
  const { message } = App.useApp();
  const { items, pagination, filters, loading, pageTotals, totals, current, detailLoading } =
    useSelector((s) => s.disposals);
  const { branches } = useSelector((s) => s.branch);

  const [searchText, setSearchText] = useState(filters.search || '');
  const [visibleColumns, setVisibleColumns] = useState(DEFAULT_DISPOSAL_COLUMNS);
  const [selectedId, setSelectedId] = useState(null);

  useEffect(() => {
    dispatch(fetchBranches());
  }, [dispatch]);

  useEffect(() => {
    dispatch(fetchDisposals());
  }, [dispatch, filters]);

  useEffect(() => {
    const timer = setTimeout(() => {
      dispatch(setDisposalFilters({ search: searchText }));
    }, 400);
    return () => clearTimeout(timer);
  }, [searchText, dispatch]);

  useEffect(() => {
    if (!selectedId) return;
    dispatch(fetchDisposalDetail(selectedId));
  }, [selectedId, dispatch]);

  const handleToggleColumn = (key, checked) => {
    setVisibleColumns((prev) => {
      if (checked) return Array.from(new Set([...prev, key]));
      return prev.filter((k) => k !== key);
    });
  };

  const columnMenuItems = Object.keys(disposalColumnCatalog).map((key) => ({
    key,
    label: (
      <Checkbox
        checked={visibleColumns.includes(key)}
        onChange={(e) => handleToggleColumn(key, e.target.checked)}
      >
        {disposalColumnCatalog[key]?.title || key}
      </Checkbox>
    ),
  }));

  const columnMenu = { items: columnMenuItems };

  const handleExport = () => {
    const headers = visibleColumns.map((c) => disposalColumnCatalog[c]?.title || c);
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
    link.download = `disposals-${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    message.success(`Đã xuất ${items.length} dòng`);
  };

  const onSelectRow = (record) => {
    setSelectedId(record.id || record.dispose_code);
  };

  const selectedDisposal = useMemo(() => {
    if (current && (current.id === selectedId || current.dispose_code === selectedId)) return current;
    return items.find((x) => x.id === selectedId || x.dispose_code === selectedId) || null;
  }, [current, selectedId, items]);

  return (
    <div className={styles.page}>
      <div className={styles.headerRow}>
        <Input
          allowClear
          prefix={<SearchOutlined />}
          placeholder="Theo mã xuất hủy"
          value={searchText}
          onChange={(e) => setSearchText(e.target.value)}
          className={styles.search}
        />
        <Space>
          <Tooltip title="Tạo phiếu xuất hủy">
            <Button type="primary" icon={<PlusOutlined />}>Xuất hủy</Button>
          </Tooltip>
          <Tooltip title="Xuất file">
            <Button icon={<DownloadOutlined />} onClick={handleExport}>Xuất file</Button>
          </Tooltip>
          <Dropdown menu={columnMenu} trigger={['click']}>
            <Button icon={<ColumnHeightOutlined />} />
          </Dropdown>
          <Button icon={<ReloadOutlined />} onClick={() => dispatch(fetchDisposals(filters))} />
        </Space>
      </div>

      <DisposalSummary totals={totals} />

      <div className={styles.layout}>
        <DisposalFilters
          filters={filters}
          onChange={(payload) => dispatch(setDisposalFilters(payload))}
          branches={branches}
        />

        <div className={styles.tableArea}>
          <DisposalTable
            data={items}
            loading={loading}
            pagination={pagination}
            onPageChange={(pageInfo) => {
              dispatch(setDisposalPage(pageInfo));
              dispatch(fetchDisposals({ ...filters, ...pageInfo }));
            }}
            onSelectRow={onSelectRow}
            selectedRowKey={selectedId}
            visibleColumns={visibleColumns}
            onToggleColumn={handleToggleColumn}
          />

          <div className={styles.pageTotals}>
            <Space size="large">
              <span>Tổng SL hủy: {pageTotals.total_quantity.toLocaleString('vi-VN')}</span>
              <span>Tổng giá trị hủy: {pageTotals.total_value.toLocaleString('vi-VN')} đ</span>
            </Space>
          </div>

          <Spin spinning={detailLoading}>
            <DisposalDetail data={selectedDisposal} />
          </Spin>
        </div>
      </div>
    </div>
  );
};

export default DisposalListPage;
