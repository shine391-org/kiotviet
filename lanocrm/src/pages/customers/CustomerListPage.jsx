// src/pages/customers/CustomerListPage.jsx

import React, { useEffect, useMemo, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import {
  App,
  Alert,
  Button,
  Card,
  Checkbox,
  Col,
  DatePicker,
  Drawer,
  Dropdown,
  Empty,
  Form,
  Input,
  InputNumber,
  Row,
  Select,
  Space,
  Table,
  Tag,
  Tooltip,
  Typography,
  Upload,
  Divider,
  Skeleton,
} from 'antd';
import {
  DeleteOutlined,
  DownloadOutlined,
  EditOutlined,
  ExportOutlined,
  ImportOutlined,
  MailOutlined,
  MoreOutlined,
  PlusOutlined,
  ReloadOutlined,
  SearchOutlined,
  SettingOutlined,
  UploadOutlined as AntUploadOutlined,
} from '@ant-design/icons';
import dayjs from 'dayjs';
import {
  fetchCustomers,
  fetchCustomer,
  createCustomer,
  updateCustomer,
  deleteCustomer,
  clearCustomerError,
} from '../../store/slices/customerSlice';
import customerApi from '../../api/customerApi';
import styles from './CustomerListPage.module.css';

const { Title, Text } = Typography;
const { RangePicker } = DatePicker;

const CUSTOMER_TYPES = [
  { label: 'Tất cả', value: null },
  { label: 'Cá nhân', value: 'INDIVIDUAL' },
  { label: 'Công ty', value: 'COMPANY' },
  { label: 'Hộ gia đình', value: 'HOUSEHOLD' },
];

const GENDERS = [
  { label: 'Tất cả', value: null },
  { label: 'Nam', value: 'MALE' },
  { label: 'Nữ', value: 'FEMALE' },
  { label: 'Khác', value: 'OTHER' },
];

const STATUSES = [
  { label: 'Tất cả', value: null },
  { label: 'Đang hoạt động', value: 'ACTIVE' },
  { label: 'Ngừng hoạt động', value: 'INACTIVE' },
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

const statusLabel = {
  ACTIVE: 'Đang hoạt động',
  INACTIVE: 'Ngừng hoạt động',
};

const typeTag = (type) => {
  if (!type) return <Tag color="default">Chưa rõ</Tag>;
  const colorMap = { INDIVIDUAL: 'blue', COMPANY: 'gold', HOUSEHOLD: 'green' };
  return <Tag color={colorMap[type] || 'default'}>{typeLabel[type] || type}</Tag>;
};

const genderTag = (gender) => {
  if (!gender) return '—';
  const colorMap = { MALE: 'blue', FEMALE: 'magenta', OTHER: 'purple' };
  return <Tag color={colorMap[gender] || 'default'}>{genderLabel[gender] || gender}</Tag>;
};

const statusTag = (status) => {
  if (!status) return <Tag>Chưa rõ</Tag>;
  const color = status === 'ACTIVE' ? 'green' : 'red';
  return <Tag color={color}>{statusLabel[status] || status}</Tag>;
};

const moneyFormat = (value) =>
  typeof value === 'number'
    ? value.toLocaleString('vi-VN', { style: 'currency', currency: 'VND' })
    : '—';

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
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [drawerMode, setDrawerMode] = useState('create');
  const [uploading, setUploading] = useState(false);
  const [visibleCols, setVisibleCols] = useState([
    'code',
    'name',
    'customer_type',
    'phone',
    'gender',
    'email',
    'facebook',
    'tax_code',
    'status',
    'current_debt',
  ]);
  const [form] = Form.useForm();

  // Fetch customers on filters change
  useEffect(() => {
    dispatch(fetchCustomers(filters));
  }, [dispatch, filters]);

  // Auto pick first row after data changes
  useEffect(() => {
    if (!items || items.length === 0) {
      setSelectedId(null);
      return;
    }
    const stillExists = items.some((c) => c.id === selectedId);
    if (!stillExists) {
      const firstId = items[0]?.id;
      setSelectedId(firstId);
      dispatch(fetchCustomer(firstId));
    }
  }, [items, selectedId, dispatch]);

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

  const allColumns = useMemo(
    () => [
      { key: 'code', title: 'Mã khách hàng', dataIndex: 'code', width: 130, render: (v, r) => v || `KH${r.id}` },
      { key: 'name', title: 'Tên khách hàng', dataIndex: 'name', render: (v) => <Text strong>{v || 'Chưa có tên'}</Text> },
      { key: 'customer_type', title: 'Loại KH', dataIndex: 'customer_type', width: 120, render: typeTag },
      { key: 'phone', title: 'Điện thoại', dataIndex: 'phone', width: 140 },
      { key: 'phone2', title: 'Điện thoại 2', dataIndex: 'phone2', width: 140 },
      { key: 'customer_group_id', title: 'Nhóm khách hàng', dataIndex: 'customer_group_id', render: (v) => v || 'Chưa có' },
      { key: 'gender', title: 'Giới tính', dataIndex: 'gender', width: 120, render: genderTag },
      { key: 'birthday', title: 'Ngày sinh', dataIndex: 'birthday', width: 140, render: (v) => (v ? dayjs(v).format('DD/MM/YYYY') : '—') },
      { key: 'email', title: 'Email', dataIndex: 'email', ellipsis: true },
      {
        key: 'facebook',
        title: 'Facebook',
        dataIndex: 'facebook',
        ellipsis: true,
        render: (value) =>
          value ? (
            <a href={value} target="_blank" rel="noreferrer">
              {value}
            </a>
          ) : (
            '—'
          ),
      },
      { key: 'company_name', title: 'Công ty', dataIndex: 'company_name' },
      { key: 'tax_code', title: 'Mã số thuế', dataIndex: 'tax_code' },
      { key: 'cccd_cmnd', title: 'Số CCCD/CMND', dataIndex: 'cccd_cmnd' },
      { key: 'address', title: 'Địa chỉ', dataIndex: 'address', ellipsis: true },
      { key: 'province', title: 'Khu vực giao hàng', dataIndex: 'province' },
      { key: 'ward', title: 'Phường/Xã', dataIndex: 'ward' },
      { key: 'created_by', title: 'Người tạo', dataIndex: 'created_by' },
      { key: 'created_at', title: 'Ngày tạo', dataIndex: 'created_at', render: (v) => (v ? dayjs(v).format('DD/MM/YYYY') : '—') },
      { key: 'notes', title: 'Ghi chú', dataIndex: 'notes', ellipsis: true },
      {
        key: 'last_transaction_at',
        title: 'Ngày giao dịch cuối',
        dataIndex: 'last_transaction_at',
        render: (v) => (v ? dayjs(v).format('DD/MM/YYYY HH:mm') : '—'),
      },
      { key: 'current_debt', title: 'Nợ hiện tại', dataIndex: 'current_debt', render: moneyFormat },
      { key: 'total_sales', title: 'Tổng bán', dataIndex: 'total_sales', render: moneyFormat },
      { key: 'total_sales_net', title: 'Tổng bán trừ trả hàng', dataIndex: 'total_sales_net', render: moneyFormat },
      { key: 'status', title: 'Trạng thái', dataIndex: 'status', render: statusTag },
    ],
    []
  );

  const columns = useMemo(
    () => allColumns.filter((c) => visibleCols.includes(c.key)),
    [allColumns, visibleCols]
  );

  const onRowClick = (record) => {
    setSelectedId(record.id);
    dispatch(fetchCustomer(record.id));
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
    setDrawerMode('create');
    form.resetFields();
    form.setFieldsValue({ customer_type: 'INDIVIDUAL', status: 'ACTIVE' });
    setDrawerOpen(true);
  };

  const openEdit = () => {
    if (!current) return;
    setDrawerMode('edit');
    form.setFieldsValue({
      ...current,
      birthday: current.birthday ? dayjs(current.birthday) : null,
    });
    setDrawerOpen(true);
  };

  const handleDelete = () => {
    if (!current) return;
    modal.confirm({
      title: 'Xác nhận xóa',
      content: `Bạn có chắc muốn xóa khách hàng "${current.name}"?`,
      okText: 'Xóa',
      okType: 'danger',
      cancelText: 'Hủy',
      onOk: async () => {
        try {
          await dispatch(deleteCustomer(current.id)).unwrap();
          message.success('Đã xóa khách hàng');
          handleRefresh();
        } catch (err) {
          modal.error({ title: 'Xóa thất bại', content: err?.message || 'Không thể xóa' });
        }
      },
    });
  };

  const handleExport = async () => {
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
          onChange={(e) => {
            setVisibleCols((prev) =>
              e.target.checked ? [...prev, c.key] : prev.filter((k) => k !== c.key)
            );
          }}
        >
          {c.title}
        </Checkbox>
      ),
    })),
  };

  const onFilterChange = (key, value) => {
    setFilters((prev) => ({ ...prev, [key]: value, page: 1 }));
  };

  const renderDetail = () => {
    if (currentLoading) {
      return <Skeleton active paragraph={{ rows: 6 }} />;
    }
    if (!current) {
      return (
        <div className={styles.emptyDetail}>
          <Empty description="Chọn một khách hàng để xem chi tiết" />
        </div>
      );
    }

    const infoRow = (label, value) => (
      <div className={styles.infoItem}>
        <Text type="secondary">{label}</Text>
        <Text strong>{value || 'Chưa có'}</Text>
      </div>
    );

    return (
      <>
        <div className={styles.detailHeader}>
          <div className={styles.avatarPlaceholder}>
            {(current.name || '?')
              .split(' ')
              .map((p) => p[0])
              .join('')
              .slice(0, 2)
              .toUpperCase()}
          </div>
          <div>
            <Title level={5} style={{ marginBottom: 2 }}>
              {current.name}
            </Title>
            <Space size={8}>
              <Tag color="blue">KH#{current.id}</Tag>
              {typeTag(current.customer_type)}
              {statusTag(current.status)}
            </Space>
          </div>
        </div>

        <Row gutter={[12, 12]} style={{ marginTop: 12 }}>
          <Col span={12}>{infoRow('Điện thoại', current.phone)}</Col>
          <Col span={12}>{infoRow('Email', current.email)}</Col>
          <Col span={12}>{infoRow('Giới tính', genderLabel[current.gender] || 'Chưa có')}</Col>
          <Col span={12}>{infoRow('Sinh nhật', current.birthday ? dayjs(current.birthday).format('DD/MM/YYYY') : 'Chưa có')}</Col>
          <Col span={12}>{infoRow('Địa chỉ', current.address)}</Col>
          <Col span={12}>{infoRow('Phường/Xã', current.ward)}</Col>
          <Col span={12}>{infoRow('Tỉnh/TP', current.province)}</Col>
          <Col span={12}>{infoRow('Công ty', current.company_name)}</Col>
          <Col span={12}>{infoRow('Mã số thuế', current.tax_code)}</Col>
          <Col span={12}>{infoRow('Nợ hiện tại', moneyFormat(current.current_debt))}</Col>
        </Row>

        <Divider style={{ margin: '16px 0' }} />
        <Space>
          <Button type="primary" icon={<EditOutlined />} onClick={openEdit}>
            Sửa
          </Button>
          <Button danger icon={<DeleteOutlined />} onClick={handleDelete}>
            Xóa
          </Button>
        </Space>
      </>
    );
  };

  const onSave = async () => {
    try {
      const values = await form.validateFields();
      const payload = {
        ...values,
        birthday: values.birthday ? values.birthday.format('YYYY-MM-DD') : null,
      };

      if (drawerMode === 'create') {
        await dispatch(createCustomer(payload)).unwrap();
        message.success('Tạo khách hàng thành công');
      } else {
        await dispatch(updateCustomer({ id: current.id, data: payload })).unwrap();
        message.success('Cập nhật khách hàng thành công');
      }

      setDrawerOpen(false);
      form.resetFields();
      handleRefresh();
    } catch (err) {
      if (err?.message) {
        modal.error({ 
          title: drawerMode === 'create' ? 'Tạo khách hàng thất bại' : 'Cập nhật thất bại', 
          content: err.message 
        });
      }
    }
  };

  return (
    <div className={styles.page}>
      <div className={styles.headerRow}>
        <Input
          className={styles.searchBar}
          prefix={<SearchOutlined />}
          placeholder="Theo mã, tên, số điện thoại"
          allowClear
          value={searchText}
          onChange={(e) => setSearchText(e.target.value)}
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
          <Button icon={<ExportOutlined />} onClick={handleExport}>
            Export
          </Button>
          <Dropdown menu={columnMenu} trigger={['click']}>
            <Button icon={<SettingOutlined />} />
          </Dropdown>
          <Button icon={<ReloadOutlined />} onClick={handleRefresh}>
            Làm mới
          </Button>
        </Space>
      </div>

      {error && (
        <Alert
          type="error"
          closable
          message="Không tải được dữ liệu khách hàng"
          description={error}
          onClose={() => dispatch(clearCustomerError())}
          style={{ marginBottom: 12 }}
        />
      )}

      <div className={styles.layout}>
        <Card title="Bộ lọc" className={styles.filterCard} size="small">
          <Space direction="vertical" size={12} style={{ width: '100%' }}>
            <div>
              <Text className={styles.filterLabel}>Loại khách hàng</Text>
              <Select
                value={filters.customer_type}
                onChange={(val) => onFilterChange('customer_type', val)}
                options={CUSTOMER_TYPES}
                style={{ width: '100%' }}
              />
            </div>
            <div>
              <Text className={styles.filterLabel}>Giới tính</Text>
              <Select
                value={filters.gender}
                onChange={(val) => onFilterChange('gender', val)}
                options={GENDERS}
                style={{ width: '100%' }}
              />
            </div>
            <div>
              <Text className={styles.filterLabel}>Trạng thái</Text>
              <Select
                value={filters.status}
                onChange={(val) => onFilterChange('status', val)}
                options={STATUSES}
                style={{ width: '100%' }}
              />
            </div>
            <div>
              <Text className={styles.filterLabel}>Ngày tạo</Text>
              <RangePicker
                style={{ width: '100%' }}
                onChange={(vals) =>
                  onFilterChange('created_from', vals ? vals[0].format('YYYY-MM-DD') : null) ||
                  onFilterChange('created_to', vals ? vals[1].format('YYYY-MM-DD') : null)
                }
              />
            </div>
            <div>
              <Text className={styles.filterLabel}>Sinh nhật</Text>
              <RangePicker
                style={{ width: '100%' }}
                onChange={(vals) =>
                  onFilterChange('birthday_from', vals ? vals[0].format('YYYY-MM-DD') : null) ||
                  onFilterChange('birthday_to', vals ? vals[1].format('YYYY-MM-DD') : null)
                }
              />
            </div>
            <div>
              <Text className={styles.filterLabel}>Giao dịch cuối</Text>
              <RangePicker
                style={{ width: '100%' }}
                onChange={(vals) =>
                  onFilterChange('last_transaction_from', vals ? vals[0].format('YYYY-MM-DD') : null) ||
                  onFilterChange('last_transaction_to', vals ? vals[1].format('YYYY-MM-DD') : null)
                }
              />
            </div>
            <div>
              <Text className={styles.filterLabel}>Nợ hiện tại (từ / đến)</Text>
              <Space>
                <InputNumber
                  style={{ width: 110 }}
                  placeholder="Từ"
                  onChange={(v) => onFilterChange('debt_from', v)}
                />
                <InputNumber
                  style={{ width: 110 }}
                  placeholder="Đến"
                  onChange={(v) => onFilterChange('debt_to', v)}
                />
              </Space>
            </div>
            <div>
              <Text className={styles.filterLabel}>Tổng bán (từ / đến)</Text>
              <Space>
                <InputNumber
                  style={{ width: 110 }}
                  placeholder="Từ"
                  onChange={(v) => onFilterChange('total_sales_from', v)}
                />
                <InputNumber
                  style={{ width: 110 }}
                  placeholder="Đến"
                  onChange={(v) => onFilterChange('total_sales_to', v)}
                />
              </Space>
            </div>
          </Space>
        </Card>

        <div className={styles.tableStack}>
          <Card
            className={styles.tableCard}
            styles={{ body: { padding: 0 } }}
            title={false}
            extra={null}
          >
            <Table
              rowKey="id"
              dataSource={items}
              columns={columns}
              loading={loading}
              pagination={{
                current: pagination.page || 1,
                pageSize: pagination.limit || 15,
                total: pagination.total || 0,
                showSizeChanger: true,
                pageSizeOptions: ['15', '20', '50', '100'],
                showTotal: (total) => `${total} khách hàng`,
              }}
              onChange={handleTableChange}
              size="middle"
              rowClassName={(record) =>
                record.id === selectedId ? styles.selectedRow : styles.clickableRow
              }
              onRow={(record) => ({
                onClick: () => onRowClick(record),
              })}
              scroll={{ x: true }}
            />
          </Card>

          <Card title="Chi tiết khách hàng" className={styles.detailCard}>
            {renderDetail()}
          </Card>
        </div>
      </div>

      <Drawer
        title={drawerMode === 'create' ? 'Tạo khách hàng' : 'Sửa khách hàng'}
        width={640}
        onClose={() => setDrawerOpen(false)}
        open={drawerOpen}
        extra={
          <Space>
            <Button onClick={() => setDrawerOpen(false)}>Bỏ qua</Button>
            <Button type="primary" loading={saving} onClick={onSave}>
              Lưu
            </Button>
          </Space>
        }
      >
        <Form layout="vertical" form={form} initialValues={{ customer_type: 'INDIVIDUAL', status: 'ACTIVE' }}>
          <Row gutter={12}>
            <Col span={12}>
              <Form.Item
                label="Tên khách hàng"
                name="name"
                rules={[{ required: true, message: 'Vui lòng nhập tên' }]}
              >
                <Input id="customer-name" placeholder="Bắt buộc" aria-label="Tên khách hàng" />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item label="Mã khách hàng" name="code">
                <Input id="customer-code" placeholder="Tự động nếu để trống" aria-label="Mã khách hàng" />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item label="Điện thoại 1" name="phone">
                <Input id="customer-phone" aria-label="Điện thoại 1" />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item label="Điện thoại 2" name="phone2">
                <Input id="customer-phone2" aria-label="Điện thoại 2" />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item label="Email" name="email">
                <Input id="customer-email" aria-label="Email" />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item label="Facebook" name="facebook">
                <Input placeholder="facebook.com/username" />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item label="Sinh nhật" name="birthday">
                <DatePicker style={{ width: '100%' }} format="DD/MM/YYYY" />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item label="Giới tính" name="gender">
                <Select allowClear options={GENDERS.filter((g) => g.value)} />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item label="Loại khách hàng" name="customer_type">
                <Select options={CUSTOMER_TYPES.filter((c) => c.value)} />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item label="Trạng thái" name="status">
                <Select options={STATUSES.filter((s) => s.value)} />
              </Form.Item>
            </Col>
          </Row>

          <Divider />
          <Title level={5}>Địa chỉ</Title>
          <Form.Item label="Địa chỉ" name="address">
            <Input placeholder="Nhập địa chỉ" />
          </Form.Item>
          <Row gutter={12}>
            <Col span={12}>
              <Form.Item label="Tỉnh/Thành phố" name="province">
                <Input />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item label="Phường/Xã" name="ward">
                <Input />
              </Form.Item>
            </Col>
          </Row>

          <Divider />
          <Title level={5}>Thông tin xuất hoá đơn</Title>
          <Row gutter={12}>
            <Col span={12}>
              <Form.Item label="Tên người mua" name="buyer_name">
                <Input />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item label="Mã số thuế" name="tax_code">
                <Input />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item label="Công ty" name="company_name">
                <Input />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item label="Email xuất hoá đơn" name="invoice_email">
                <Input />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item label="Số điện thoại hoá đơn" name="invoice_phone">
                <Input />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item label="Số CCCD/CMND" name="cccd_cmnd">
                <Input />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item label="Số hộ chiếu" name="id_number">
                <Input />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item label="Ngân hàng" name="bank_name">
                <Input />
              </Form.Item>
            </Col>
            <Col span={12}>
              <Form.Item label="Số tài khoản ngân hàng" name="bank_account">
                <Input />
              </Form.Item>
            </Col>
          </Row>

          <Divider />
          <Form.Item label="Ghi chú" name="notes">
            <Input.TextArea rows={3} />
          </Form.Item>
        </Form>
      </Drawer>
    </div>
  );
};

export default CustomerListPage;
