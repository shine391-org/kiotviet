import React, { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import {
  App,
  Input,
  Button,
  Space,
  Tooltip,
  Dropdown,
  Checkbox,
  Drawer,
  Grid,
} from 'antd';
import {
  SearchOutlined,
  PlusOutlined,
  DownloadOutlined,
  SettingOutlined,
  QuestionCircleOutlined,
  ColumnHeightOutlined,
  ReloadOutlined,
  FilterOutlined,
} from '@ant-design/icons';
import InvoiceFilters from '../../components/invoices/InvoiceFilters';
import InvoiceTable from '../../components/invoices/InvoiceTable';
import { invoiceColumnCatalog } from '../../components/invoices/InvoiceTable';
import {
  fetchInvoices,
  fetchInvoiceDetail,
  setInvoiceFilters,
  setInvoicePage,
} from '../../store/slices/invoiceSlice';
import { fetchBranches } from '../../store/slices/branchSlice';
import { DEFAULT_INVOICE_COLUMNS } from '../../constants/invoices';
import styles from './InvoiceListPage.module.css';

const InvoiceListPage = () => {
  const dispatch = useDispatch();
  const { message } = App.useApp();
  const screens = Grid.useBreakpoint();
  const { items, pagination, filters, loading, pageTotals, current, detailLoading } =
    useSelector((s) => s.invoices);
  const { branches } = useSelector((s) => s.branch);

  const [searchText, setSearchText] = useState(filters.search || '');
  const [visibleColumns, setVisibleColumns] = useState(DEFAULT_INVOICE_COLUMNS);
  const [selectedId, setSelectedId] = useState(null);
  const [isFilterOpen, setIsFilterOpen] = useState(false);

  // Treat screens narrower than ~lg (992px) as "mobile" for layout purposes
  const isMobile = !screens.lg;

  useEffect(() => {
    setIsFilterOpen(!isMobile);
  }, [isMobile]);

  useEffect(() => {
    dispatch(fetchBranches());
  }, [dispatch]);

  useEffect(() => {
    dispatch(fetchInvoices());
  }, [dispatch, filters]);

  useEffect(() => {
    const timer = setTimeout(() => {
      dispatch(setInvoiceFilters({ search: searchText }));
    }, 400);
    return () => clearTimeout(timer);
  }, [searchText, dispatch]);

  useEffect(() => {
    if (!selectedId) return;
    dispatch(fetchInvoiceDetail(selectedId));
  }, [selectedId, dispatch]);

  const handleToggleColumn = (key, checked) => {
    setVisibleColumns((prev) => {
      if (checked) return Array.from(new Set([...prev, key]));
      return prev.filter((k) => k !== key);
    });
  };

  const columnMenuItems = Object.keys(invoiceColumnCatalog).map((key) => ({
    key,
    label: (
      <Checkbox
        checked={visibleColumns.includes(key)}
        onChange={(e) => handleToggleColumn(key, e.target.checked)}
      >
        {invoiceColumnCatalog[key]?.title || key}
      </Checkbox>
    ),
  }));

  const columnMenu = { items: columnMenuItems };

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
    link.download = `invoices-${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    message.success(`Đã xuất ${items.length} dòng`);
  };

  const onSelectRow = (record) => {
    if (record) {
      setSelectedId(record.id || record.invoice_code);
    } else {
      setSelectedId(null);
    }
  };

  return (
    <div className={styles.page}>
      <div className={styles.headerRow}>
        <Input
          allowClear
          aria-label="Theo mã hóa đơn"
          prefix={<SearchOutlined />}
          placeholder="Theo mã hóa đơn"
          value={searchText}
          onChange={(e) => setSearchText(e.target.value)}
          className={styles.search}
        />
        <Space>
          {isMobile && (
            <Button icon={<FilterOutlined />} onClick={() => setIsFilterOpen(true)}>
              Bộ lọc
            </Button>
          )}
          <Tooltip title="Tạo mới">
            <Button type="primary" icon={<PlusOutlined />}>Tạo mới</Button>
          </Tooltip>
          <Tooltip title="Xuất file">
            <Button icon={<DownloadOutlined />} onClick={handleExport}>Xuất file</Button>
          </Tooltip>
          <Dropdown menu={columnMenu} trigger={['click']}>
            <Button icon={<ColumnHeightOutlined />} />
          </Dropdown>
          <Tooltip title="Cài đặt đơn hàng">
            <Button icon={<SettingOutlined />} href="/Settings?SettingType=orders" target="_blank" />
          </Tooltip>
          <Tooltip title="Hướng dẫn quản lý hóa đơn">
            <Button icon={<QuestionCircleOutlined />} href="https://help.kiotviet.vn/hoa-don" target="_blank" />
          </Tooltip>
          <Button icon={<ReloadOutlined />} onClick={() => dispatch(fetchInvoices(filters))} />
        </Space>
      </div>

      <div className={styles.layout}>
        {!isMobile && (
          <InvoiceFilters
            filters={filters}
            onChange={(payload) => dispatch(setInvoiceFilters(payload))}
            branches={branches}
          />
        )}

        {isMobile && (
          <Drawer
            title="Bộ lọc"
            placement="left"
            open={isFilterOpen}
            onClose={() => setIsFilterOpen(false)}
            width="100%"
            styles={{ body: { padding: 12 } }}
          >
            <InvoiceFilters
              filters={filters}
              onChange={(payload) => dispatch(setInvoiceFilters(payload))}
              branches={branches}
            />
          </Drawer>
        )}

        <div className={styles.tableArea}>
          <InvoiceTable
            data={items}
            loading={loading}
            pagination={pagination}
            onPageChange={(pageInfo) => {
              dispatch(setInvoicePage(pageInfo));
              dispatch(fetchInvoices({ ...filters, ...pageInfo }));
            }}
            onSelectRow={onSelectRow}
            selectedRowKey={selectedId}
            visibleColumns={visibleColumns}
            onToggleColumn={handleToggleColumn}
            detailData={current}
            detailLoading={detailLoading}
          />

          <div className={styles.pageTotals}>
            <Space size="large">
              <span>Khách cần trả: {pageTotals.customer_payable.toLocaleString('vi-VN')} đ</span>
              <span>Khách đã trả: {pageTotals.customer_paid.toLocaleString('vi-VN')} đ</span>
              <span>COD: {pageTotals.cod_amount.toLocaleString('vi-VN')} đ</span>
              <span>Phí trả ĐTGH: {pageTotals.shipping_fee.toLocaleString('vi-VN')} đ</span>
            </Space>
          </div>
        </div>
      </div>
    </div>
  );
};

export default InvoiceListPage;
