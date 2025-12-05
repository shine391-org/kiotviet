import React, { useEffect, useMemo, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { App, message as antdMessage, Checkbox } from 'antd';
import TransferToolbar from '../../components/transfers/TransferToolbar';
import TransferFilters from '../../components/transfers/TransferFilters';
import TransferTable from '../../components/transfers/TransferTable';
import TransferDetailPanel from '../../components/transfers/TransferDetailPanel';
import {
  fetchTransfers,
  fetchTransferDetail,
  setTransferFilters,
  setTransferPage,
  setExpandedCode,
  duplicateTransfer,
  openTransfer,
  saveReceivingNotes,
} from '../../store/slices/transferSlice';
import { fetchBranches } from '../../store/slices/branchSlice';
import { DEFAULT_TRANSFER_COLUMNS } from '../../constants/transfers';
import styles from './TransferListPage.module.css';
import { useNavigate } from 'react-router-dom';

const STORAGE_KEY = 'lano_transfer_visible_columns';
const COLUMN_LABELS = {
  favorite: 'Ưa thích',
  code: 'Mã chuyển hàng',
  creatorName: 'Người tạo',
  receiverName: 'Người nhận',
  transferDate: 'Ngày chuyển',
  receiveDate: 'Ngày nhận',
  createdAt: 'Thời gian tạo',
  fromBranch: 'Từ chi nhánh',
  toBranch: 'Tới chi nhánh',
  quantitySent: 'Tổng SL chuyển',
  valueSent: 'Giá trị chuyển',
  quantityReceived: 'Tổng SL nhận',
  valueReceived: 'Giá trị nhận',
  totalItems: 'Tổng số mặt hàng',
  notes: 'Ghi chú',
  status: 'Trạng thái',
};

const TransferListPage = () => {
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const { message } = App.useApp ? App.useApp() : { message: antdMessage };
  const { items, pagination, filters, loading, summary, detailLoading, expandedCode, details } = useSelector(
    (s) => s.transfers
  );
  const { branches } = useSelector((s) => s.branch);

  const [searchText, setSearchText] = useState(filters.search || '');
  const [visibleColumns, setVisibleColumns] = useState(() => {
    const stored = localStorage.getItem(STORAGE_KEY);
    return stored ? JSON.parse(stored) : DEFAULT_TRANSFER_COLUMNS;
  });
  const [showFilters, setShowFilters] = useState(true);

  useEffect(() => {
    dispatch(fetchBranches());
  }, [dispatch]);

  useEffect(() => {
    dispatch(fetchTransfers());
  }, [dispatch, filters]);

  useEffect(() => {
    const timer = setTimeout(() => {
      dispatch(setTransferFilters({ search: searchText }));
    }, 350);
    return () => clearTimeout(timer);
  }, [searchText, dispatch]);

  useEffect(() => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(visibleColumns));
  }, [visibleColumns]);

  const handleFiltersChange = (payload) => dispatch(setTransferFilters(payload));

  const handlePageChange = ({ page, limit, sort }) => {
    if (sort) {
      dispatch(setTransferFilters({ sort, page: 1, limit: limit || filters.limit }));
      dispatch(fetchTransfers({ sort, page: 1, limit: limit || filters.limit }));
      return;
    }
    dispatch(setTransferPage({ page, limit }));
    dispatch(fetchTransfers({ page, limit }));
  };

  const handleToggleColumn = (key, checked) => {
    setVisibleColumns((prev) => {
      if (checked) return Array.from(new Set([...prev, key]));
      return prev.filter((c) => c !== key);
    });
  };

  const handleExpandRow = (expanded, record) => {
    if (expanded) {
      dispatch(fetchTransferDetail(record.code));
    } else {
      dispatch(setExpandedCode(null));
    }
  };

  const expandedRowKeys = useMemo(() => (expandedCode ? [expandedCode] : []), [expandedCode]);

  const renderDetail = (record) => {
    const detail = details[record.code] || record;
    return (
      <TransferDetailPanel
        transfer={detail}
        onDuplicate={(code) =>
          dispatch(duplicateTransfer(code)).then(() => message.success('Đã sao chép phiếu chuyển'))
        }
        onOpen={(code) => dispatch(openTransfer(code)).then(() => message.success('Đã mở phiếu'))}
        onSaveNotes={(code, notes) =>
          dispatch(saveReceivingNotes({ code, receivingNotes: notes })).then(() => message.success('Đã lưu ghi chú'))
        }
        onExport={() => message.info('Tính năng xuất sẽ kết nối backend sau')}
        onPrintLabel={() => message.info('Tính năng in tem đang chờ backend')}
        onPrint={() => message.info('Tính năng in đang chờ backend')}
        onCancel={() => message.info('Hủy phiếu cần xác nhận backend')}
      />
    );
  };

  const columnMenu = {
    items: Object.keys(COLUMN_LABELS).map((key) => ({
      key,
      label: (
        <Checkbox
          checked={visibleColumns.includes(key)}
          onChange={(e) => handleToggleColumn(key, e.target.checked)}
        >
          {COLUMN_LABELS[key]}
        </Checkbox>
      ),
    })),
  };

  return (
    <div className={styles.page}>
      <TransferToolbar
        searchText={searchText}
        onSearchChange={setSearchText}
        onToggleFilters={() => setShowFilters((v) => !v)}
        onCreate={() => navigate('/inventory/transfer/create')}
        onImport={() => message.info('Import sẽ kết nối backend')}
        onExport={() => message.info('Export sẽ dùng bộ lọc hiện tại')}
        columnMenu={columnMenu}
      />

      <div className={styles.layout}>
        {showFilters && (
          <div className={styles.sidebar}>
            <TransferFilters filters={filters} branches={branches} onChange={handleFiltersChange} />
          </div>
        )}

        <div className={styles.main}>
          <TransferTable
            data={items}
            loading={loading}
            pagination={pagination}
            onPageChange={handlePageChange}
            onExpandRow={handleExpandRow}
            expandedRowKeys={expandedRowKeys}
            visibleColumns={visibleColumns}
            onToggleColumn={handleToggleColumn}
            summary={summary}
            renderDetail={renderDetail}
            detailLoading={detailLoading}
          />
        </div>
      </div>
    </div>
  );
};

export default TransferListPage;
