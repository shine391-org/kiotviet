// src/pages/suppliers/SupplierListPage.jsx

import React, { useEffect, useMemo, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import {
  App,
  Button,
  Card,
  Checkbox,
  DatePicker,
  Divider,
  Dropdown,
  Input,
  InputNumber,
  Radio,
  Select,
  Space,
  Table,
  Tabs,
  Tag,
  Tooltip,
} from 'antd';
import {
  PlusOutlined,
  ImportOutlined,
  ExportOutlined,
  ReloadOutlined,
  SettingOutlined,
  FilterOutlined,
  AppstoreOutlined,
  UnorderedListOutlined,
} from '@ant-design/icons';
import dayjs from 'dayjs';
import styles from './SupplierListPage.module.css';
import { fetchSuppliers, fetchSupplier } from '../../store/slices/supplierSlice';
import supplierApi from '../../api/supplierApi';
import CreateSupplierModal from './CreateSupplierModal';
import EditSupplierModal from './EditSupplierModal';
import AdjustDebtModal from './AdjustDebtModal';
import PaymentModal from './PaymentModal';
import DiscountModal from './DiscountModal';
import SupplierDetailPanel from './SupplierDetailPanel';
import ReceiptDetailModal from './ReceiptDetailModal';

const { RangePicker } = DatePicker;

/**
 * SupplierListPage
 * @agent-layer: frontend-page
 * @agent-pattern: list-with-sticky-filter + detail tabs
 * @agent-reusable: MEDIUM
 */
const statusColor = {
  ACTIVE: 'blue',
  INACTIVE: 'red',
};

const currency = (value) => {
  if (value === null || value === undefined) return '0';
  return new Intl.NumberFormat('vi-VN').format(value);
};

const SupplierListPage = () => {
  const { message } = App.useApp();
  const dispatch = useDispatch();

  const { items, pagination, loading, current, currentLoading, summary } = useSelector(
    (state) => state.supplier
  );

  const [filters, setFilters] = useState({
    page: 1,
    limit: 15,
    search: '',
    status: 'ACTIVE',
    total_from: null,
    total_to: null,
    time_mode: 'all',
  });
  const [searchText, setSearchText] = useState('');
  const [selectedId, setSelectedId] = useState(null);
  const [payableType, setPayableType] = useState('ALL');
  const [createModalOpen, setCreateModalOpen] = useState(false);
  const [editModalOpen, setEditModalOpen] = useState(false);
  const [editingSupplier, setEditingSupplier] = useState(null);
  const [adjustModalOpen, setAdjustModalOpen] = useState(false);
  const [paymentModalOpen, setPaymentModalOpen] = useState(false);
  const [discountModalOpen, setDiscountModalOpen] = useState(false);
  const [selectedSupplierForAction, setSelectedSupplierForAction] = useState(null);
  const [receiptModalOpen, setReceiptModalOpen] = useState(false);
  const [selectedReceiptCode, setSelectedReceiptCode] = useState(null);

  // Handler for opening edit modal
  const handleOpenEdit = (supplier) => {
    setEditingSupplier(supplier);
    setEditModalOpen(true);
  };

  // Handlers for debt action modals
  const handleOpenAdjust = (supplier) => {
    setSelectedSupplierForAction(supplier);
    setAdjustModalOpen(true);
  };
  const handleOpenPayment = (supplier) => {
    setSelectedSupplierForAction(supplier);
    setPaymentModalOpen(true);
  };
  const handleOpenDiscount = (supplier) => {
    setSelectedSupplierForAction(supplier);
    setDiscountModalOpen(true);
  };

  // Handler for clicking on receipt code
  const handleReceiptClick = (code) => {
    setSelectedReceiptCode(code);
    setReceiptModalOpen(true);
  };

  // Full column definitions matching KiotViet design
  const allColumns = useMemo(
    () => [
      { key: 'code', title: 'Mã nhà cung cấp', dataIndex: 'code', width: 140 },
      {
        key: 'name',
        title: 'Tên nhà cung cấp',
        dataIndex: 'name',
        render: (text) => <strong>{text || 'Chưa có tên'}</strong>,
        width: 200,
      },
      { key: 'phone', title: 'Điện thoại', dataIndex: 'phone', width: 130 },
      {
        key: 'group',
        title: 'Nhóm nhà cung cấp',
        dataIndex: 'group',
        render: (v) => v || 'Chưa có',
        width: 160,
      },
      { key: 'email', title: 'Email', dataIndex: 'email', ellipsis: true, width: 180 },
      { key: 'address', title: 'Địa chỉ', dataIndex: 'address', ellipsis: true, width: 200 },
      { key: 'area', title: 'Khu vực', dataIndex: 'area', width: 180 },
      { key: 'ward', title: 'Phường/Xã', dataIndex: 'ward', width: 140 },
      { key: 'company', title: 'Công ty', dataIndex: 'company', width: 160 },
      { key: 'note', title: 'Ghi chú', dataIndex: 'note', ellipsis: true, width: 160 },
      { key: 'tax_code', title: 'Mã số thuế', dataIndex: 'tax_code', width: 140 },
      {
        key: 'creator',
        title: 'Người tạo',
        dataIndex: 'creator',
        width: 140,
        render: (v) => v || '—',
      },
      {
        key: 'created_at',
        title: 'Ngày tạo',
        dataIndex: 'created_at',
        width: 120,
        render: (v) => (v ? dayjs(v).format('DD/MM/YYYY') : '—'),
      },
      {
        key: 'current_debt',
        title: 'Nợ cần trả hiện tại',
        dataIndex: 'current_debt',
        align: 'right',
        width: 160,
        render: (v) => currency(v),
      },
      {
        key: 'total_purchase',
        title: 'Tổng mua',
        dataIndex: 'total_purchase',
        align: 'right',
        width: 140,
        render: (v) => currency(v),
      },
      {
        key: 'total_purchase_after_return',
        title: 'Tổng mua trừ trả hàng',
        dataIndex: 'total_purchase_after_return',
        align: 'right',
        width: 180,
        render: (v) => currency(v),
      },
      {
        key: 'status',
        title: 'Trạng thái',
        dataIndex: 'status',
        width: 130,
        render: (v) => <Tag color={statusColor[v] || 'default'}>{v === 'ACTIVE' ? 'Đang hoạt động' : 'Ngừng hoạt động'}</Tag>,
      },
    ],
    []
  );

  // Default visible columns matching KiotViet design
  const defaultVisibleCols = ['code', 'name', 'phone', 'email', 'current_debt', 'total_purchase'];
  const [visibleCols, setVisibleCols] = useState(defaultVisibleCols);
  const columns = useMemo(
    () => allColumns.filter((col) => visibleCols.includes(col.key)),
    [allColumns, visibleCols]
  );

  // Fetch suppliers when filters change
  useEffect(() => {
    dispatch(fetchSuppliers(filters));
  }, [dispatch, filters]);

  // Fetch supplier detail when selection changes (user click)
  useEffect(() => {
    if (selectedId) {
      dispatch(fetchSupplier(selectedId));
    }
  }, [selectedId, dispatch]);

  // Debounce search
  useEffect(() => {
    const timer = setTimeout(() => {
      setFilters((prev) => ({ ...prev, search: searchText, page: 1 }));
    }, 350);
    return () => clearTimeout(timer);
  }, [searchText]);

  const toggleColumn = (key) => {
    setVisibleCols((prev) =>
      prev.includes(key) ? prev.filter((k) => k !== key) : [...prev, key]
    );
  };

  const handleStatusChange = (status) => {
    setFilters((prev) => ({ ...prev, status, page: 1 }));
  };

  const groupOptions = useMemo(() => {
    const groups = Array.from(new Set(items.map((i) => i.group).filter(Boolean)));
    return groups.map((g) => ({ label: g, value: g }));
  }, [items]);

  const payableData = useMemo(() => current?.payables || [], [current?.payables, payableType]);

  // Summary data from API (or local fallback)
  const summaryData = useMemo(() => {
    if (summary && (summary.total_debt !== 0 || summary.total_purchase !== 0)) {
      return { totalDebt: summary.total_debt, totalPurchase: summary.total_purchase };
    }
    // Fallback to local calculation if API summary not available
    const totalDebt = items.reduce((sum, item) => sum + (item.current_debt || 0), 0);
    const totalPurchase = items.reduce((sum, item) => sum + (item.total_purchase || 0), 0);
    return { totalDebt, totalPurchase };
  }, [items, summary]);

  const columnMenu = {
    items: allColumns.map((col) => ({
      key: col.key,
      label: (
        <Checkbox
          checked={visibleCols.includes(col.key)}
          onChange={() => toggleColumn(col.key)}
        >
          {col.title}
        </Checkbox>
      ),
    })),
    className: styles.columnsMenu,
  };

  return (
    <div className={styles.page}>
      <div className={styles.content}>
        <Card
          title="Nhà cung cấp"
          className={styles.filterCard}
          size="small"
          bordered
          headStyle={{ padding: '8px 12px 6px', margin: 0 }}
          bodyStyle={{ padding: '10px 12px 12px' }}
        >
          <div className={styles.filterGroup}>
            <div className={styles.filterLabel}>
              <span>Nhóm nhà cung cấp</span>
              <Button type="link" size="small">Tạo mới</Button>
            </div>
            <Select
              style={{ width: '100%' }}
              placeholder="Tất cả các nhóm"
              allowClear
              options={groupOptions}
              onChange={(value) => setFilters((prev) => ({ ...prev, group: value, page: 1 }))}
            />
          </div>

          <div className={styles.filterGroup}>
            <div className={styles.filterLabel}>Tổng mua</div>
            <Space direction="vertical" style={{ width: '100%' }}>
              <InputNumber
                style={{ width: '100%' }}
                placeholder="Từ - Nhập giá trị"
                min={0}
                value={filters.total_from}
                onChange={(value) => setFilters((prev) => ({ ...prev, total_from: value, page: 1 }))}
              />
              <InputNumber
                style={{ width: '100%' }}
                placeholder="Tới - Nhập giá trị"
                min={0}
                value={filters.total_to}
                onChange={(value) => setFilters((prev) => ({ ...prev, total_to: value, page: 1 }))}
              />
            </Space>
          </div>

          <div className={styles.filterGroup}>
            <div className={styles.filterLabel}>Thời gian</div>
            <Radio.Group
              onChange={(e) => setFilters((prev) => ({ ...prev, time_mode: e.target.value }))}
              defaultValue="all"
            >
              <Space direction="vertical">
                <Radio value="all">Toàn thời gian</Radio>
                <Radio value="custom">
                  <Space>
                    Tùy chỉnh
                    <RangePicker size="small" disabled={(filters.time_mode || 'all') !== 'custom'} />
                  </Space>
                </Radio>
              </Space>
            </Radio.Group>
          </div>

          <div className={styles.filterGroup}>
            <div className={styles.filterLabel}>Nợ hiện tại</div>
            <Space direction="vertical" style={{ width: '100%' }}>
              <InputNumber
                style={{ width: '100%' }}
                placeholder="Từ - Nhập giá trị"
                value={filters.debt_from}
                onChange={(value) => setFilters((prev) => ({ ...prev, debt_from: value, page: 1 }))}
              />
              <InputNumber
                style={{ width: '100%' }}
                placeholder="Tới - Nhập giá trị"
                value={filters.debt_to}
                onChange={(value) => setFilters((prev) => ({ ...prev, debt_to: value, page: 1 }))}
              />
            </Space>
          </div>

          <div className={styles.filterGroup}>
            <div className={styles.filterLabel}>Trạng thái</div>
            <div className={styles.statusChips}>
              <Button
                type={!filters.status ? 'primary' : 'default'}
                onClick={() => handleStatusChange(null)}
              >
                Tất cả
              </Button>
              <Button
                type={filters.status === 'ACTIVE' ? 'primary' : 'default'}
                onClick={() => handleStatusChange('ACTIVE')}
              >
                Đang hoạt động
              </Button>
              <Button
                type={filters.status === 'INACTIVE' ? 'primary' : 'default'}
                danger={filters.status === 'INACTIVE'}
                onClick={() => handleStatusChange('INACTIVE')}
              >
                Ngừng hoạt động
              </Button>
            </div>
          </div>
        </Card>

        <div className={styles.contentMain}>
          <div className={styles.tablePanel}>
            <div className={styles.toolbarRow}>
              <Input
                className={styles.searchBox}
                placeholder="Theo mã, tên, số điện thoại"
                prefix={<FilterOutlined />}
                value={searchText}
                onChange={(e) => setSearchText(e.target.value)}
                allowClear
                size="middle"
              />
              <Space className={styles.actions} wrap>
                <Button type="primary" icon={<PlusOutlined />} onClick={() => setCreateModalOpen(true)}>
                  Nhà cung cấp
                </Button>
                <Button icon={<ImportOutlined />} onClick={() => message.info('Import file sẽ được nối API khi sẵn sàng.')}>
                  Import file
                </Button>
                <Button icon={<ExportOutlined />} onClick={() => message.success('Đã chuẩn bị dữ liệu mẫu để xuất.')}>
                  Xuất file
                </Button>
                <Dropdown menu={columnMenu} trigger={['click']}>
                  <Button icon={<SettingOutlined />} />
                </Dropdown>
                <Tooltip title="Làm mới">
                  <Button icon={<ReloadOutlined />} onClick={() => dispatch(fetchSuppliers(filters))} />
                </Tooltip>
              </Space>
            </div>

            <div className={styles.tableWrapper}>
              {/* Summary Row */}
              <div className={styles.summaryRow}>
                <div className={styles.summaryItem}>
                  <span className={styles.summaryLabel}>Nợ cần trả hiện tại</span>
                  <span className={styles.summaryValue}>{currency(summaryData.totalDebt)}</span>
                </div>
                <div className={styles.summaryItem}>
                  <span className={styles.summaryLabel}>Tổng mua</span>
                  <span className={styles.summaryValue}>{currency(summaryData.totalPurchase)}</span>
                </div>
              </div>
              <Table
                rowKey="id"
                loading={loading}
                bordered
                dataSource={items}
                columns={columns}
                pagination={{
                  current: pagination.page,
                  pageSize: pagination.limit,
                  total: pagination.total,
                  showSizeChanger: true,
                  showTotal: (total, range) => `${range[0]}-${range[1]} trong ${total} nhà cung cấp`,
                  onChange: (page, pageSize) => setFilters((prev) => ({ ...prev, page, limit: pageSize })),
                }}
                rowSelection={{
                  type: 'checkbox',
                  selectedRowKeys: selectedId ? [selectedId] : [],
                  onChange: (keys) => {
                    const id = keys[keys.length - 1];
                    if (id && id !== selectedId) {
                      setSelectedId(id);
                      dispatch(fetchSupplier(id));
                    }
                  },
                }}
                expandable={{
                  expandedRowKeys: selectedId ? [selectedId] : [],
                  expandIcon: () => null, // Hide default expand icon
                  expandedRowRender: (record) => (
                    <SupplierDetailPanel
                      supplier={current?.id === record.id ? current : record}
                      loading={currentLoading}
                      onReceiptClick={handleReceiptClick}
                      onEdit={handleOpenEdit}
                      onInvoiceInfoClick={handleOpenEdit}
                      onDelete={async (id) => {
                        try {
                          await supplierApi.deleteSupplier(id);
                          message.success('Đã xóa nhà cung cấp thành công!');
                          setSelectedId(null);
                          dispatch(fetchSuppliers(filters));
                        } catch (err) {
                          message.error(err.message || 'Xóa thất bại');
                        }
                      }}
                      onToggleStatus={async (id, newStatus) => {
                        try {
                          await supplierApi.updateSupplier(id, { status: newStatus });
                          message.success(`Đã ${newStatus === 'INACTIVE' ? 'ngừng' : 'kích hoạt'} nhà cung cấp!`);
                          dispatch(fetchSupplier(id));
                          dispatch(fetchSuppliers(filters));
                        } catch (err) {
                          message.error(err.message || 'Cập nhật thất bại');
                        }
                      }}
                      onAdjust={handleOpenAdjust}
                      onPayment={handleOpenPayment}
                      onDiscount={handleOpenDiscount}
                    />
                  ),
                }}
                onRow={(record) => ({
                  onClick: () => {
                    if (record.id === selectedId) {
                      // Click same row = collapse
                      setSelectedId(null);
                    } else {
                      // Click different row = expand this one
                      setSelectedId(record.id);
                      dispatch(fetchSupplier(record.id));
                    }
                  },
                  className: record.id === selectedId ? styles.selectedRow : '',
                })}
                size="middle"
                scroll={{ x: 1100 }}
              />
            </div>
          </div>
        </div>

        {/* Create Supplier Modal */}
        <CreateSupplierModal
          open={createModalOpen}
          onCancel={() => setCreateModalOpen(false)}
          onSuccess={() => {
            dispatch(fetchSuppliers(filters));
            message.success('Tạo nhà cung cấp thành công!');
          }}
          supplierGroups={groupOptions.map((g) => g.value)}
        />

        {/* Receipt Detail Modal */}
        <ReceiptDetailModal
          open={receiptModalOpen}
          onCancel={() => setReceiptModalOpen(false)}
          receiptCode={selectedReceiptCode}
        />

        {/* Edit Supplier Modal */}
        <EditSupplierModal
          open={editModalOpen}
          supplier={editingSupplier}
          onCancel={() => {
            setEditModalOpen(false);
            setEditingSupplier(null);
          }}
          onSuccess={() => {
            dispatch(fetchSuppliers(filters));
            if (selectedId) dispatch(fetchSupplier(selectedId));
          }}
          supplierGroups={groupOptions.map((g) => g.value)}
        />

        {/* Adjust Debt Modal */}
        <AdjustDebtModal
          open={adjustModalOpen}
          supplier={selectedSupplierForAction}
          onCancel={() => {
            setAdjustModalOpen(false);
            setSelectedSupplierForAction(null);
          }}
          onSuccess={() => {
            dispatch(fetchSuppliers(filters));
            if (selectedId) dispatch(fetchSupplier(selectedId));
          }}
        />

        {/* Payment Modal */}
        <PaymentModal
          open={paymentModalOpen}
          supplier={selectedSupplierForAction}
          onCancel={() => {
            setPaymentModalOpen(false);
            setSelectedSupplierForAction(null);
          }}
          onSuccess={() => {
            dispatch(fetchSuppliers(filters));
            if (selectedId) dispatch(fetchSupplier(selectedId));
          }}
        />

        {/* Discount Modal */}
        <DiscountModal
          open={discountModalOpen}
          supplier={selectedSupplierForAction}
          onCancel={() => {
            setDiscountModalOpen(false);
            setSelectedSupplierForAction(null);
          }}
          onSuccess={() => {
            dispatch(fetchSuppliers(filters));
            if (selectedId) dispatch(fetchSupplier(selectedId));
          }}
        />
      </div>
    </div>
  );
};

export default SupplierListPage;
