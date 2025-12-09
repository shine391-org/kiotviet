// src/pages/customers/CustomerListPage.jsx

import React, { useEffect, useMemo, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import {
  App,
  Alert,
  Button,
  Card,
  Checkbox,
  DatePicker,
  Dropdown,
  Input,
  InputNumber,
  Radio,
  Select,
  Space,
  Table,
  Tooltip,
  Upload,
} from 'antd';
import {
  FilterOutlined,
  ImportOutlined,
  MailOutlined,
  PlusOutlined,
  ReloadOutlined,
  SettingOutlined,
  MenuOutlined,
  UnorderedListOutlined,
  AppstoreOutlined,
} from '@ant-design/icons';
import dayjs from 'dayjs';
import {
  fetchCustomers,
  fetchCustomer,
  updateCustomer,
  deleteCustomer,
  clearCustomerError,
} from '../../store/slices/customerSlice';
import customerApi from '../../api/customerApi';
import CustomerDetailPanel from './CustomerDetailPanel';
import CreateCustomerModal from './CreateCustomerModal';
import styles from './CustomerListPage.module.css';

const { RangePicker } = DatePicker;

const CUSTOMER_TYPES = [
  { label: 'Tất cả', value: null },
  { label: 'Cá nhân', value: 'INDIVIDUAL' },
  { label: 'Công ty', value: 'COMPANY' },
];

const GENDERS = [
  { label: 'Tất cả', value: null },
  { label: 'Nam', value: 'MALE' },
  { label: 'Nữ', value: 'FEMALE' },
];

const typeLabel = {
  INDIVIDUAL: 'Cá nhân',
  COMPANY: 'Công ty',
  HOUSEHOLD: 'Hộ gia đình',
};

const genderLabel = {
  MALE: 'Nam',
  FEMALE: 'Nữ',
  OTHER: 'Khác',
};

const currency = (value) => {
  if (value === null || value === undefined) return '0';
  return new Intl.NumberFormat('vi-VN').format(value);
};

const CustomerListPage = () => {
  const dispatch = useDispatch();
  const { modal, message } = App.useApp();

  const { items, pagination, loading, current, currentLoading, error, saving } = useSelector(
    (state) => state.customer
  );

  const [filters, setFilters] = useState({
    page: 1,
    limit: 15,
    search: '',
    customer_type: null,
    gender: null,
    status: null,
  });
  const [searchText, setSearchText] = useState('');
  const [selectedId, setSelectedId] = useState(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [editingCustomer, setEditingCustomer] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [customerGroups, setCustomerGroups] = useState([]);
  const [visibleCols, setVisibleCols] = useState([
    'code',
    'name',
    'phone',
    'current_debt',
    'total_sales',
    'total_sales_net',
  ]);

  // Fetch customer groups
  useEffect(() => {
    const loadGroups = async () => {
      try {
        const response = await customerApi.getGroups();
        setCustomerGroups(response.data || []);
      } catch (err) {
        console.error('Failed to load customer groups:', err);
      }
    };
    loadGroups();
  }, []);

  // Fetch customers on filters change
  useEffect(() => {
    dispatch(fetchCustomers(filters));
  }, [dispatch, filters]);

  // Fetch customer detail when selection changes
  useEffect(() => {
    if (selectedId) {
      dispatch(fetchCustomer(selectedId));
    }
  }, [selectedId, dispatch]);

  // Debounce search text → filters
  useEffect(() => {
    const timer = setTimeout(() => {
      setFilters((prev) => {
        if (prev.search === searchText) return prev;
        return { ...prev, search: searchText, page: 1 };
      });
    }, 400);
    return () => clearTimeout(timer);
  }, [searchText]);

  // Calculate summary from items
  const summaryData = useMemo(() => {
    const totalDebt = items.reduce((sum, item) => sum + (item.current_debt || 0), 0);
    const totalSales = items.reduce((sum, item) => sum + (item.total_sales || 0), 0);
    const totalSalesNet = items.reduce((sum, item) => sum + (item.total_sales_net || 0), 0);
    return { totalDebt, totalSales, totalSalesNet };
  }, [items]);

  const allColumns = useMemo(
    () => [
      { key: 'code', title: 'Mã khách hàng', dataIndex: 'code', width: 130, render: (v, r) => v || `KH${r.id}` },
      {
        key: 'name',
        title: 'Tên khách hàng',
        dataIndex: 'name',
        render: (v) => <strong>{v || 'Chưa có tên'}</strong>,
        width: 180,
      },
      { key: 'phone', title: 'Điện thoại', dataIndex: 'phone', width: 130 },
      {
        key: 'current_debt',
        title: 'Nợ hiện tại',
        dataIndex: 'current_debt',
        align: 'right',
        width: 140,
        render: (v) => currency(v),
      },
      {
        key: 'total_sales',
        title: 'Tổng bán',
        dataIndex: 'total_sales',
        align: 'right',
        width: 140,
        render: (v) => currency(v),
      },
      {
        key: 'total_sales_net',
        title: 'Tổng bán trừ trả hàng',
        dataIndex: 'total_sales_net',
        align: 'right',
        width: 180,
        render: (v) => currency(v),
      },
      { key: 'customer_type', title: 'Loại KH', dataIndex: 'customer_type', width: 100, render: (v) => typeLabel[v] || v || '—' },
      { key: 'gender', title: 'Giới tính', dataIndex: 'gender', width: 100, render: (v) => genderLabel[v] || '—' },
      { key: 'email', title: 'Email', dataIndex: 'email', ellipsis: true, width: 180 },
      { key: 'facebook', title: 'Facebook', dataIndex: 'facebook', ellipsis: true, width: 160 },
      { key: 'birthday', title: 'Ngày sinh', dataIndex: 'birthday', width: 120, render: (v) => (v ? dayjs(v).format('DD/MM/YYYY') : '—') },
      { key: 'address', title: 'Địa chỉ', dataIndex: 'address', ellipsis: true, width: 200 },
      { key: 'created_at', title: 'Ngày tạo', dataIndex: 'created_at', width: 120, render: (v) => (v ? dayjs(v).format('DD/MM/YYYY') : '—') },
    ],
    []
  );

  const columns = useMemo(
    () => allColumns.filter((c) => visibleCols.includes(c.key)),
    [allColumns, visibleCols]
  );

  const toggleColumn = (key) => {
    setVisibleCols((prev) =>
      prev.includes(key) ? prev.filter((k) => k !== key) : [...prev, key]
    );
  };

  const handleTableChange = (pag) => {
    setFilters((prev) => ({
      ...prev,
      page: pag.current,
      limit: pag.pageSize,
    }));
  };

  const handleRefresh = () => {
    dispatch(fetchCustomers(filters));
    if (selectedId) {
      dispatch(fetchCustomer(selectedId));
    }
  };

  const openCreate = () => {
    setEditingCustomer(null);
    setModalOpen(true);
  };

  const openEdit = (customer) => {
    if (!customer) return;
    setEditingCustomer(customer);
    setModalOpen(true);
  };

  const handleDelete = async (id) => {
    try {
      await dispatch(deleteCustomer(id)).unwrap();
      message.success('Đã xóa khách hàng');
      setSelectedId(null);
      handleRefresh();
    } catch (err) {
      modal.error({ title: 'Xóa thất bại', content: err?.message || 'Không thể xóa' });
    }
  };

  const handleToggleStatus = async (id, newStatus) => {
    try {
      await dispatch(updateCustomer({ id, data: { status: newStatus } })).unwrap();
      message.success(`Đã ${newStatus === 'INACTIVE' ? 'ngừng hoạt động' : 'kích hoạt'} khách hàng`);
      dispatch(fetchCustomer(id));
      dispatch(fetchCustomers(filters));
    } catch (err) {
      message.error(err.message || 'Cập nhật thất bại');
    }
  };

  const handleExport = async () => {
    setExporting(true);
    try {
      const response = await customerApi.exportCustomers(filters);
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'customers.csv');
      document.body.appendChild(link);
      link.click();
      link.parentNode.removeChild(link);
      message.success('Đã xuất file CSV');
    } catch (err) {
      modal.error({ title: 'Xuất file thất bại', content: err?.message || 'Không thể xuất' });
    } finally {
      setExporting(false);
    }
  };

  const handleImport = async ({ file }) => {
    setUploading(true);
    try {
      await customerApi.importCustomers(file);
      message.success('Import thành công');
      handleRefresh();
    } catch (err) {
      modal.error({ title: 'Import thất bại', content: err?.message || 'Không thể import' });
    } finally {
      setUploading(false);
    }
  };

  const columnMenu = {
    items: allColumns.map((c) => ({
      key: c.key,
      label: (
        <Checkbox
          checked={visibleCols.includes(c.key)}
          onChange={() => toggleColumn(c.key)}
        >
          {c.title}
        </Checkbox>
      ),
    })),
    className: styles.columnsMenu,
  };

  const onFilterChange = (key, value) => {
    setFilters((prev) => ({ ...prev, [key]: value, page: 1 }));
  };

  const handleStatusChange = (status) => {
    setFilters((prev) => ({ ...prev, status, page: 1 }));
  };

  const handleModalSuccess = () => {
    handleRefresh();
  };

  const groupOptions = useMemo(() => {
    return customerGroups.map((g) => ({ label: g.name || g.name_vi, value: g.id }));
  }, [customerGroups]);

  return (
    <div className={styles.page}>
      <div className={styles.content}>
        {/* Filter Sidebar */}
        <Card
          title="Khách hàng"
          className={styles.filterCard}
          size="small"
          bordered
          extra={
            <Button type="link" size="small" onClick={openCreate}>
              Tạo mới
            </Button>
          }
        >
          <div className={styles.filterGroup}>
            <div className={styles.filterLabel}>Nhóm khách hàng</div>
            <Select
              style={{ width: '100%' }}
              placeholder="Tất cả các nhóm"
              allowClear
              options={groupOptions}
              onChange={(value) => onFilterChange('customer_group_id', value)}
            />
          </div>

          <div className={styles.filterGroup}>
            <div className={styles.filterLabel}>Chi nhánh tạo ●</div>
            <Select
              style={{ width: '100%' }}
              placeholder="Chọn chi nhánh"
              allowClear
              onChange={(value) => onFilterChange('branch_id', value)}
            />
          </div>

          <div className={styles.filterGroup}>
            <div className={styles.filterLabel}>Ngày tạo</div>
            <Radio.Group
              defaultValue="all"
              onChange={(e) => {
                if (e.target.value === 'all') {
                  onFilterChange('created_from', null);
                  onFilterChange('created_to', null);
                }
              }}
            >
              <Space direction="vertical">
                <Radio value="all">Toàn thời gian</Radio>
                <Radio value="custom">
                  <RangePicker
                    size="small"
                    onChange={(vals) => {
                      if (vals) {
                        onFilterChange('created_from', vals[0].format('YYYY-MM-DD'));
                        onFilterChange('created_to', vals[1].format('YYYY-MM-DD'));
                      }
                    }}
                  />
                </Radio>
              </Space>
            </Radio.Group>
          </div>

          <div className={styles.filterGroup}>
            <div className={styles.filterLabel}>Người tạo</div>
            <Select
              style={{ width: '100%' }}
              placeholder="Chọn người tạo"
              allowClear
              onChange={(value) => onFilterChange('created_by', value)}
            />
          </div>

          <div className={styles.filterGroup}>
            <div className={styles.filterLabel}>Loại khách hàng</div>
            <div className={styles.statusChips}>
              {CUSTOMER_TYPES.map((t) => (
                <Button
                  key={t.value || 'all'}
                  type={filters.customer_type === t.value ? 'primary' : 'default'}
                  onClick={() => onFilterChange('customer_type', t.value)}
                >
                  {t.label}
                </Button>
              ))}
            </div>
          </div>

          <div className={styles.filterGroup}>
            <div className={styles.filterLabel}>Giới tính</div>
            <div className={styles.statusChips}>
              {GENDERS.map((g) => (
                <Button
                  key={g.value || 'all'}
                  type={filters.gender === g.value ? 'primary' : 'default'}
                  onClick={() => onFilterChange('gender', g.value)}
                >
                  {g.label}
                </Button>
              ))}
            </div>
          </div>

          <div className={styles.filterGroup}>
            <div className={styles.filterLabel}>Sinh nhật</div>
            <Radio.Group defaultValue="all">
              <Space direction="vertical">
                <Radio value="all">Toàn thời gian</Radio>
                <Radio value="custom">
                  <RangePicker
                    size="small"
                    onChange={(vals) => {
                      if (vals) {
                        onFilterChange('birthday_from', vals[0].format('YYYY-MM-DD'));
                        onFilterChange('birthday_to', vals[1].format('YYYY-MM-DD'));
                      }
                    }}
                  />
                </Radio>
              </Space>
            </Radio.Group>
          </div>

          <div className={styles.filterGroup}>
            <div className={styles.filterLabel}>Ngày giao dịch cuối</div>
            <Radio.Group defaultValue="all">
              <Space direction="vertical">
                <Radio value="all">Toàn thời gian</Radio>
                <Radio value="custom">
                  <RangePicker
                    size="small"
                    onChange={(vals) => {
                      if (vals) {
                        onFilterChange('last_transaction_from', vals[0].format('YYYY-MM-DD'));
                        onFilterChange('last_transaction_to', vals[1].format('YYYY-MM-DD'));
                      }
                    }}
                  />
                </Radio>
              </Space>
            </Radio.Group>
          </div>

          <div className={styles.filterGroup}>
            <div className={styles.filterLabel}>Tổng bán</div>
            <div className={styles.filterSubLabel}>Giá trị</div>
            <Space direction="vertical" style={{ width: '100%' }}>
              <InputNumber
                style={{ width: '100%' }}
                placeholder="Từ - Nhập giá trị"
                min={0}
                onChange={(value) => onFilterChange('total_sales_from', value)}
              />
              <InputNumber
                style={{ width: '100%' }}
                placeholder="Tới - Nhập giá trị"
                min={0}
                onChange={(value) => onFilterChange('total_sales_to', value)}
              />
            </Space>
            <div className={styles.filterSubLabel} style={{ marginTop: 8 }}>Thời gian</div>
            <Radio.Group defaultValue="all">
              <Space direction="vertical">
                <Radio value="all">Toàn thời gian</Radio>
                <Radio value="custom">
                  <RangePicker size="small" />
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
                onChange={(value) => onFilterChange('debt_from', value)}
              />
              <InputNumber
                style={{ width: '100%' }}
                placeholder="Tới - Nhập giá trị"
                onChange={(value) => onFilterChange('debt_to', value)}
              />
            </Space>
          </div>
        </Card>

        {/* Main Content */}
        <div className={styles.contentMain}>
          <div className={styles.tablePanel}>
            {/* Toolbar */}
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
                <Button type="primary" icon={<PlusOutlined />} onClick={openCreate}>
                  Khách hàng
                </Button>
                <Tooltip title="Gửi tin nhắn (sắp ra mắt)">
                  <Button icon={<MailOutlined />} disabled>
                    Gửi tin nhắn
                  </Button>
                </Tooltip>
                <Upload
                  accept=".csv"
                  showUploadList={false}
                  customRequest={handleImport}
                  disabled={uploading}
                >
                  <Button icon={<ImportOutlined />} loading={uploading}>
                    Import file
                  </Button>
                </Upload>
                <Dropdown menu={columnMenu} trigger={['click']}>
                  <Button icon={<MenuOutlined />} />
                </Dropdown>
                <Button icon={<UnorderedListOutlined />} />
                <Button icon={<AppstoreOutlined />} />
                <Tooltip title="Cài đặt (sắp ra mắt)">
                  <Button icon={<SettingOutlined />} disabled />
                </Tooltip>
                <Tooltip title="Làm mới">
                  <Button icon={<ReloadOutlined />} onClick={handleRefresh} />
                </Tooltip>
              </Space>
            </div>

            {error && (
              <Alert
                type="error"
                closable
                message="Không tải được dữ liệu khách hàng"
                description={error}
                onClose={() => dispatch(clearCustomerError())}
                style={{ margin: '12px 16px 0' }}
              />
            )}

            {/* Summary Row */}
            <div className={styles.summaryRow}>
              <div className={styles.summaryItem}>
                <span className={styles.summaryLabel}>Nợ hiện tại</span>
                <span className={styles.summaryValue}>{currency(summaryData.totalDebt)}</span>
              </div>
              <div className={styles.summaryItem}>
                <span className={styles.summaryLabel}>Tổng bán</span>
                <span className={styles.summaryValue}>{currency(summaryData.totalSales)}</span>
              </div>
              <div className={styles.summaryItem}>
                <span className={styles.summaryLabel}>Tổng bán trừ trả hàng</span>
                <span className={styles.summaryValue}>{currency(summaryData.totalSalesNet)}</span>
              </div>
            </div>

            {/* Table */}
            <div className={styles.tableWrapper}>
              <Table
                rowKey="id"
                loading={loading}
                bordered
                dataSource={items}
                columns={columns}
                pagination={{
                  current: pagination.page || 1,
                  pageSize: pagination.limit || 15,
                  total: pagination.total || 0,
                  showSizeChanger: true,
                  pageSizeOptions: ['15', '20', '50', '100'],
                  showTotal: (total, range) => `${range[0]}-${range[1]} trong ${total} khách hàng`,
                  onChange: (page, pageSize) => setFilters((prev) => ({ ...prev, page, limit: pageSize })),
                }}
                rowSelection={{
                  type: 'checkbox',
                  selectedRowKeys: selectedId ? [selectedId] : [],
                  onChange: (keys) => {
                    const id = keys[keys.length - 1];
                    if (id && id !== selectedId) {
                      setSelectedId(id);
                      dispatch(fetchCustomer(id));
                    }
                  },
                }}
                expandable={{
                  expandedRowKeys: selectedId ? [selectedId] : [],
                  expandIcon: () => null,
                  expandedRowRender: (record) => (
                    <CustomerDetailPanel
                      customer={current?.id === record.id ? current : record}
                      loading={currentLoading}
                      onEdit={openEdit}
                      onDelete={handleDelete}
                      onToggleStatus={handleToggleStatus}
                      onInvoiceInfoClick={(c) => {
                        // TODO: Open invoice info modal
                        openEdit(c);
                      }}
                      onAddAddress={(c) => {
                        // TODO: Open add address modal
                        message.info('Chức năng thêm địa chỉ sẽ được cập nhật');
                      }}
                      onOrderClick={(code) => {
                        // TODO: Open order detail modal
                        message.info(`Xem chi tiết đơn hàng: ${code}`);
                      }}
                      onReceiptClick={(code) => {
                        // TODO: Open receipt detail modal
                        message.info(`Xem chi tiết phiếu: ${code}`);
                      }}
                      onPayment={(c) => {
                        // TODO: Open payment modal
                        message.info('Chức năng thanh toán sẽ được cập nhật');
                      }}
                      onAdjust={(c) => {
                        // TODO: Open adjust debt modal
                        message.info('Chức năng điều chỉnh sẽ được cập nhật');
                      }}
                      onDiscount={(c) => {
                        // TODO: Open discount modal
                        message.info('Chức năng chiết khấu sẽ được cập nhật');
                      }}
                      onCreateQR={(c) => {
                        // TODO: Open QR modal
                        message.info('Chức năng tạo QR sẽ được cập nhật');
                      }}
                    />
                  ),
                }}
                onRow={(record) => ({
                  onClick: () => {
                    if (record.id === selectedId) {
                      setSelectedId(null);
                    } else {
                      setSelectedId(record.id);
                      dispatch(fetchCustomer(record.id));
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
      </div>

      {/* Create/Edit Modal */}
      <CreateCustomerModal
        open={modalOpen}
        customer={editingCustomer}
        onCancel={() => {
          setModalOpen(false);
          setEditingCustomer(null);
        }}
        onSuccess={handleModalSuccess}
        customerGroups={customerGroups}
      />
    </div>
  );
};

export default CustomerListPage;
