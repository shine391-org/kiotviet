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
import ReturnFilters from '../../components/returns/ReturnFilters';
import ReturnTable, { returnColumnCatalog } from '../../components/returns/ReturnTable';
import ReturnSummary from '../../components/returns/ReturnSummary';
import ReturnDetail from '../../components/returns/ReturnDetail';
import {
  fetchReturns,
  fetchReturnDetail,
  setReturnFilters,
  setReturnPage,
} from '../../store/slices/returnSlice';
import { fetchBranches } from '../../store/slices/branchSlice';
import { DEFAULT_RETURN_COLUMNS } from '../../constants/returns';
import styles from './ReturnListPage.module.css';

const ReturnListPage = () => {
  const dispatch = useDispatch();
  const { message } = App.useApp();
  const { items, pagination, filters, loading, pageTotals, totals, current, detailLoading } =
    useSelector((s) => s.returns);
  const { branches } = useSelector((s) => s.branch);

  const [searchText, setSearchText] = useState(filters.search || '');
  const [visibleColumns, setVisibleColumns] = useState(DEFAULT_RETURN_COLUMNS);
  const [selectedId, setSelectedId] = useState(null);

  useEffect(() => {
    dispatch(fetchBranches());
  }, [dispatch]);

  useEffect(() => {
    dispatch(fetchReturns());
  }, [dispatch, filters]);

  useEffect(() => {
    const timer = setTimeout(() => {
      dispatch(setReturnFilters({ search: searchText }));
    }, 400);
    return () => clearTimeout(timer);
  }, [searchText, dispatch]);

  useEffect(() => {
    if (!selectedId) return;
    dispatch(fetchReturnDetail(selectedId));
  }, [selectedId, dispatch]);

  const handleToggleColumn = (key, checked) => {
    setVisibleColumns((prev) => {
      if (checked) return Array.from(new Set([...prev, key]));
      return prev.filter((k) => k !== key);
    });
  };

  const handleExport = () => {
    const headers = visibleColumns.map((c) => returnColumnCatalog[c]?.title || c);
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
    link.download = `returns-${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    message.success(`Đã xuất ${items.length} dòng`);
  };

  const onSelectRow = (record) => {
    setSelectedId(record.id || record.return_code);
  };

  const selectedReturn = useMemo(() => {
    if (current && (current.id === selectedId || current.return_code === selectedId)) return current;
    return items.find((x) => x.id === selectedId || x.return_code === selectedId) || null;
  }, [current, selectedId, items]);

  const columnMenuItems = Object.keys(returnColumnCatalog).map((key) => ({
    key,
    label: (
      <Checkbox
        checked={visibleColumns.includes(key)}
        onChange={(e) => handleToggleColumn(key, e.target.checked)}
      >
        {returnColumnCatalog[key]?.title || key}
      </Checkbox>
    ),
  }));

  const columnMenu = { items: columnMenuItems };

  return (
    <div className={styles.page}>
      <div className={styles.headerRow}>
        <Input
          allowClear
          prefix={<SearchOutlined />}
          placeholder="Theo mã phiếu trả"
          value={searchText}
          onChange={(e) => setSearchText(e.target.value)}
          className={styles.search}
        />
        <Space>
          <Tooltip title="Tạo phiếu trả hàng">
            <Button type="primary" icon={<PlusOutlined />}>Trả hàng</Button>
          </Tooltip>
          <Tooltip title="Xuất file">
            <Button icon={<DownloadOutlined />} onClick={handleExport}>Xuất file</Button>
          </Tooltip>
          <Dropdown menu={columnMenu} trigger={['click']}>
            <Button icon={<ColumnHeightOutlined />} />
          </Dropdown>
          <Button icon={<ReloadOutlined />} onClick={() => dispatch(fetchReturns(filters))} />
        </Space>
      </div>

      <ReturnSummary totals={totals} />

      <div className={styles.layout}>
        <ReturnFilters
          filters={filters}
          onChange={(payload) => dispatch(setReturnFilters(payload))}
          branches={branches}
        />

        <div className={styles.tableArea}>
          <ReturnTable
            data={items}
            loading={loading}
            pagination={pagination}
            onPageChange={(pageInfo) => {
              dispatch(setReturnPage(pageInfo));
              dispatch(fetchReturns({ ...filters, ...pageInfo }));
            }}
            onSelectRow={onSelectRow}
            selectedRowKey={selectedId}
            visibleColumns={visibleColumns}
            onToggleColumn={handleToggleColumn}
          />

          <div className={styles.pageTotals}>
            <Space size="large">
              <span>Tổng tiền hàng: {pageTotals.goods_total.toLocaleString('vi-VN')} đ</span>
              <span>Cần trả khách: {pageTotals.need_refund.toLocaleString('vi-VN')} đ</span>
              <span>Đã trả khách: {pageTotals.refunded.toLocaleString('vi-VN')} đ</span>
            </Space>
          </div>

          <Spin spinning={detailLoading}>
            <ReturnDetail data={selectedReturn} />
          </Spin>
        </div>
      </div>
    </div>
  );
};

export default ReturnListPage;
