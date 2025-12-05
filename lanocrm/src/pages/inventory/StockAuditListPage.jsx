import React, { useEffect, useMemo, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { App, Checkbox, message as antdMessage } from 'antd';
import { useNavigate } from 'react-router-dom';
import StockAuditToolbar from '../../components/stockAudits/StockAuditToolbar';
import StockAuditFilters from '../../components/stockAudits/StockAuditFilters';
import StockAuditTable from '../../components/stockAudits/StockAuditTable';
import {
  fetchStockAudits,
  setStockAuditFilters,
  setStockAuditPage,
} from '../../store/slices/stockAuditSlice';
import { DEFAULT_STOCK_AUDIT_COLUMNS, STOCK_AUDIT_COLUMN_LABELS } from '../../constants/stockAudits';
import styles from './StockAuditListPage.module.css';

const STORAGE_KEY = 'lano_stock_audit_visible_columns';

const StockAuditListPage = () => {
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const { message } = App.useApp ? App.useApp() : { message: antdMessage };
  const { items, pagination, filters, loading } = useSelector((s) => s.stockAudits);

  const [searchText, setSearchText] = useState(filters.search || '');
  const [visibleColumns, setVisibleColumns] = useState(() => {
    const stored = localStorage.getItem(STORAGE_KEY);
    return stored ? JSON.parse(stored) : DEFAULT_STOCK_AUDIT_COLUMNS;
  });
  const [showFilters, setShowFilters] = useState(true);

  useEffect(() => {
    dispatch(fetchStockAudits());
  }, [dispatch, filters]);

  useEffect(() => {
    const timer = setTimeout(() => {
      dispatch(setStockAuditFilters({ search: searchText }));
    }, 350);
    return () => clearTimeout(timer);
  }, [searchText, dispatch]);

  useEffect(() => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(visibleColumns));
  }, [visibleColumns]);

  const creatorNames = useMemo(() => {
    const set = new Set();
    (items || []).forEach((item) => {
      if (item.creatorName) set.add(item.creatorName);
    });
    return Array.from(set);
  }, [items]);

  const handleFiltersChange = (payload) => dispatch(setStockAuditFilters(payload));

  const handlePageChange = ({ page, limit, sort }) => {
    if (sort) {
      dispatch(setStockAuditFilters({ sort, page: 1, limit: limit || filters.limit }));
      dispatch(fetchStockAudits({ sort, page: 1, limit: limit || filters.limit }));
      return;
    }
    dispatch(setStockAuditPage({ page, limit }));
    dispatch(fetchStockAudits({ page, limit }));
  };

  const handleToggleColumn = (key, checked) => {
    setVisibleColumns((prev) => {
      if (checked) return Array.from(new Set([...prev, key]));
      return prev.filter((c) => c !== key);
    });
  };

  const columnMenu = useMemo(
    () => ({
      items: Object.keys(STOCK_AUDIT_COLUMN_LABELS).map((key) => ({
        key,
        label: (
          <Checkbox
            checked={visibleColumns.includes(key)}
            onChange={(e) => handleToggleColumn(key, e.target.checked)}
          >
            {STOCK_AUDIT_COLUMN_LABELS[key]}
          </Checkbox>
        ),
      })),
    }),
    [visibleColumns]
  );

  return (
    <div className={styles.page}>
      <StockAuditToolbar
        searchText={searchText}
        onSearchChange={setSearchText}
        onToggleFilters={() => setShowFilters((v) => !v)}
        onCreate={() => navigate('/inventory/audit/create')}
        onExport={() => message.info('Export sẽ dùng bộ lọc hiện tại khi backend sẵn sàng')}
        onSettings={() => message.info('Cài đặt trang sẽ được bật khi backend sẵn sàng')}
        onHelp={() => window.open('https://docs.lano.ai/stock-audit-help', '_blank')}
        columnMenu={columnMenu}
      />

      <div className={styles.layout}>
        {showFilters && (
          <div className={styles.sidebar}>
            <StockAuditFilters filters={filters} creators={creatorNames} onChange={handleFiltersChange} />
          </div>
        )}

        <div className={styles.main}>
          <StockAuditTable
            data={items}
            loading={loading}
            pagination={pagination}
            onPageChange={handlePageChange}
            visibleColumns={visibleColumns}
            onToggleColumn={handleToggleColumn}
          />
        </div>
      </div>
    </div>
  );
};

export default StockAuditListPage;
