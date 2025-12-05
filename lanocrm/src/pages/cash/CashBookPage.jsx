import React, { useEffect, useMemo, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import {
  App,
  Input,
  Button,
  Alert,
} from 'antd';
import {
  SearchOutlined,
  PlusOutlined,
  DownloadOutlined,
} from '@ant-design/icons';
import dayjs from 'dayjs';
import styles from './CashBookPage.module.css';
import CashFilters from '../../components/cash/CashFilters';
import CashSummary from '../../components/cash/CashSummary';
import CashTable from '../../components/cash/CashTable';
import CashTransactionForm from '../../components/cash/CashTransactionForm';
import {
  fetchCashTransactions,
  setCashFilters,
  setCashPage,
  fetchCashSummary,
  fetchCashBalance,
  createCashReceipt,
  createCashPayment,
  deleteCashTransaction,
} from '../../store/slices/cashSlice';
import { fetchBranches } from '../../store/slices/branchSlice';
import cashApi from '../../api/cashApi';
import ErrorAlert from '../../components/common/ErrorAlert';
import { SUMMARY_SAMPLE_LIMIT } from '../../constants/cash';

const CashBookPage = () => {
  const dispatch = useDispatch();
  const { message, modal } = App.useApp();
  const {
    items,
    pagination,
    loading,
    summary,
    summaryLoading,
    filters,
    pageTotals,
    error,
  } = useSelector((state) => state.cash);
  const { branches, loading: branchLoading } = useSelector((state) => state.branch);

  const [searchTerm, setSearchTerm] = useState(filters.search || '');
  const [showReceiptModal, setShowReceiptModal] = useState(false);
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [exporting, setExporting] = useState(false);

  useEffect(() => {
    dispatch(fetchBranches());
  }, [dispatch]);

  useEffect(() => {
    dispatch(fetchCashTransactions(filters));
    dispatch(fetchCashSummary({ filters }));
    dispatch(fetchCashBalance({
      branchId: filters.branch_id || null,
      filters: {
        date_from: filters.date_from,
        date_to: filters.date_to,
      },
    }));
  }, [dispatch, filters]);

  // Debounce search input to avoid spamming API
  useEffect(() => {
    const id = setTimeout(() => {
      dispatch(setCashFilters({ search: searchTerm }));
    }, 450);
    return () => clearTimeout(id);
  }, [searchTerm, dispatch]);

  const branchesMap = useMemo(() => {
    const map = {};
    branches.forEach((b) => {
      map[b.id] = b.name || b.code || `CN #${b.id}`;
    });
    return map;
  }, [branches]);

  const refreshData = () => {
    dispatch(fetchCashTransactions(filters));
    dispatch(fetchCashSummary({ filters }));
    dispatch(fetchCashBalance(filters.branch_id || null));
  };

  const handleCreate = async (payload, type) => {
    try {
      if (type === 'receipt') {
        await dispatch(createCashReceipt(payload)).unwrap();
      } else {
        await dispatch(createCashPayment(payload)).unwrap();
      }
      message.success('Tạo phiếu thành công');
      setShowReceiptModal(false);
      setShowPaymentModal(false);
      refreshData();
    } catch (err) {
      message.error(err || 'Không thể tạo phiếu');
    }
  };

  const handleDelete = (record) => {
    modal.confirm({
      title: 'Hủy phiếu thu/chi?',
      content: `Bạn chắc chắn muốn hủy phiếu ${record.type === 'RECEIPT' ? 'thu' : 'chi'} ${record.id}?`,
      okText: 'Hủy phiếu',
      cancelText: 'Đóng',
      okButtonProps: { danger: true },
      onOk: async () => {
        try {
          await dispatch(deleteCashTransaction(record.id)).unwrap();
          message.success('Đã hủy phiếu');
          refreshData();
        } catch (err) {
          message.error(err || 'Không thể hủy');
        }
      },
    });
  };

  const handleExport = async () => {
    setExporting(true);
    try {
      const res = await cashApi.getTransactions({
        ...filters,
        page: 1,
        limit: SUMMARY_SAMPLE_LIMIT,
      });
      const rows = res.data || [];
      const headers = [
        'Mã phiếu',
        'Loại',
        'Ngày giao dịch',
        'Số tiền',
        'Loại thu/chi',
        'Chi nhánh',
        'Người nộp/nhận',
        'Phương thức',
        'Trạng thái',
      ];
      const csv = [
        headers.join(','),
        ...rows.map((r) =>
          [
            r.reference_code || `CT-${r.id}`,
            r.type,
            r.transaction_date,
            r.amount,
            r.category,
            branchesMap[r.branch_id] || r.branch_id,
            r.payer_name || '',
            r.payment_method || '',
            r.status || '',
          ]
            .map((cell) => `"${String(cell ?? '').replace(/"/g, '""')}"`)
            .join(',')
        ),
      ].join('\n');

      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = `cashbook-${dayjs().format('YYYYMMDD-HHmm')}.csv`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      message.success(`Đã xuất ${rows.length} dòng (tối đa 500)`);
    } catch (err) {
      message.error(err.message || 'Xuất file thất bại');
    } finally {
      setExporting(false);
    }
  };

  return (
    <div className={styles.page}>
      <div className={styles.header}>
        <div className={styles.title}>Sổ quỹ tiền mặt</div>
        <Input
          allowClear
          placeholder="Theo mã phiếu, người nộp/nhận, nội dung..."
          prefix={<SearchOutlined />}
          className={styles.searchBar}
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
        />
        <div className={styles.actions}>
          <Button
            type="primary"
            icon={<PlusOutlined />}
            onClick={() => setShowReceiptModal(true)}
          >
            + Phiếu thu
          </Button>
          <Button
            type="primary"
            ghost
            icon={<PlusOutlined />}
            onClick={() => setShowPaymentModal(true)}
          >
            + Phiếu chi
          </Button>
          <Button
            icon={<DownloadOutlined />}
            loading={exporting}
            onClick={handleExport}
          >
            Xuất file
          </Button>
        </div>
      </div>

      {error && (
        <Alert
          style={{ marginTop: 12 }}
          type="error"
          message={error}
          showIcon
        />
      )}

      <CashSummary
        summary={summary}
        loading={summaryLoading}
        summaryLimited={summary.limited}
      />
      <ErrorAlert error={error} />

      <div className={styles.layout}>
        <CashFilters
          filters={filters}
          onChange={(payload) => dispatch(setCashFilters(payload))}
          branches={branches}
          loading={loading || branchLoading}
        />
        <CashTable
          data={items}
          loading={loading}
          pagination={pagination}
          onPageChange={(pageInfo) => dispatch(setCashPage(pageInfo))}
          onDelete={handleDelete}
          onRefresh={refreshData}
          onExport={handleExport}
          branchesMap={branchesMap}
          summaryLimited={summary.limited}
          pageTotals={pageTotals}
        />
      </div>

      <CashTransactionForm
        mode="receipt"
        open={showReceiptModal}
        onCancel={() => setShowReceiptModal(false)}
        onSubmit={(payload) => handleCreate(payload, 'receipt')}
        branches={branches}
        loading={loading}
      />

      <CashTransactionForm
        mode="payment"
        open={showPaymentModal}
        onCancel={() => setShowPaymentModal(false)}
        onSubmit={(payload) => handleCreate(payload, 'payment')}
        branches={branches}
        loading={loading}
      />
    </div>
  );
};

export default CashBookPage;
