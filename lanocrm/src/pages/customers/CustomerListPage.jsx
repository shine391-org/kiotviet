// src/pages/customers/CustomerListPage.jsx

import React, { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import {
  Card,
  Table,
  Input,
  Tag,
  Space,
  Button,
  Typography,
  Divider,
  Segmented,
  Empty,
  Skeleton,
  Alert,
  Modal,
  Form,
  Select,
  App,
  Tooltip,
} from 'antd';
import {
  PlusOutlined,
  SearchOutlined,
  ReloadOutlined,
  ImportOutlined,
  MailOutlined,
} from '@ant-design/icons';
import {
  fetchCustomers,
  fetchCustomer,
  createCustomer,
  clearCustomerError,
} from '../../store/slices/customerSlice';
import styles from './CustomerListPage.module.css';

const { Title, Text } = Typography;

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

const typeTag = (type) => {
  if (!type) return <Tag color="default">Chưa rõ</Tag>;
  const colorMap = {
    INDIVIDUAL: 'blue',
    COMPANY: 'gold',
    HOUSEHOLD: 'green',
  };
  return <Tag color={colorMap[type] || 'default'}>{typeLabel[type] || type}</Tag>;
};

const genderTag = (gender) => {
  if (!gender) return '—';
  const colorMap = { MALE: 'blue', FEMALE: 'magenta', OTHER: 'purple' };
  return <Tag color={colorMap[gender] || 'default'}>{genderLabel[gender] || gender}</Tag>;
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
  });
  const [searchText, setSearchText] = useState('');
  const [selectedId, setSelectedId] = useState(null);
  const [createModalOpen, setCreateModalOpen] = useState(false);
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

  const columns = [
    { title: 'Mã KH', dataIndex: 'id', key: 'id', width: 90 },
    {
      title: 'Tên khách hàng',
      dataIndex: 'name',
      key: 'name',
      render: (value) => <Text strong>{value || 'Chưa có tên'}</Text>,
    },
    {
      title: 'Loại khách hàng',
      dataIndex: 'customer_type',
      key: 'customer_type',
      render: (value) => typeTag(value),
      width: 140,
    },
    { title: 'Điện thoại', dataIndex: 'phone', key: 'phone', width: 140 },
    {
      title: 'Giới tính',
      dataIndex: 'gender',
      key: 'gender',
      width: 120,
      render: (value) => genderTag(value),
    },
    { title: 'Email', dataIndex: 'email', key: 'email', ellipsis: true },
    {
      title: 'Facebook',
      dataIndex: 'facebook',
      key: 'facebook',
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
  ];

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

  const handleTypeChange = (value) => {
    setFilters((prev) => ({ ...prev, customer_type: value || null, page: 1 }));
  };

  const handleGenderChange = (value) => {
    setFilters((prev) => ({ ...prev, gender: value || null, page: 1 }));
  };

  const handleRefresh = () => {
    dispatch(fetchCustomers(filters));
    if (selectedId) {
      dispatch(fetchCustomer(selectedId));
    }
  };

  const handleCreate = async () => {
    try {
      const values = await form.validateFields();
      await dispatch(createCustomer(values)).unwrap();
      message.success('Tạo khách hàng thành công');
      setCreateModalOpen(false);
      form.resetFields();
      dispatch(fetchCustomers(filters));
    } catch (err) {
      const msg = typeof err === 'string' ? err : err?.message || 'Không thể tạo khách hàng';
      modal.error({ title: 'Tạo khách hàng thất bại', content: msg });
    }
  };

  const renderDetail = () => {
    if (currentLoading) {
      return <Skeleton active paragraph={{ rows: 5 }} />;
    }
    if (!current) {
      return (
        <div className={styles.emptyDetail}>
          <Empty description="Chọn một khách hàng để xem chi tiết" />
        </div>
      );
    }

    const initials = (current.name || '?')
      .split(' ')
      .map((p) => p[0])
      .join('')
      .slice(0, 2)
      .toUpperCase();

    const infoRow = (label, value) => (
      <div className={styles.infoItem}>
        <Text type="secondary">{label}</Text>
        <Text strong>{value || 'Chưa có'}</Text>
      </div>
    );

    return (
      <>
        <div className={styles.detailHeader}>
          <div className={styles.avatarPlaceholder}>{initials}</div>
          <div>
            <Title level={5} style={{ marginBottom: 2 }}>
              {current.name}
            </Title>
            <Space size={8}>
              <Tag color="blue">KH#{current.id}</Tag>
              {typeTag(current.customer_type)}
            </Space>
          </div>
        </div>

        <Divider />

        <div className={styles.infoGrid}>
          {infoRow('Điện thoại', current.phone)}
          {infoRow('Email', current.email)}
          {infoRow('Giới tính', genderLabel[current.gender] || 'Chưa có')}
          {infoRow('Facebook', current.facebook)}
          {infoRow('Mã số thuế', current.tax_code)}
          {infoRow('Công ty', current.company_name)}
          {infoRow('Ghi chú', current.notes)}
          {infoRow('Người mua hàng', current.buyer_name)}
        </div>

        <Divider />
        <Space>
          <Button type="primary">Chỉnh sửa</Button>
          <Button danger ghost>Ngưng hoạt động</Button>
        </Space>
      </>
    );
  };

  return (
    <div className={styles.page}>
      <div className={styles.headerRow}>
        <Input
          className={styles.searchBar}
          prefix={<SearchOutlined />}
          placeholder="Tìm theo mã, tên, số điện thoại..."
          allowClear
          value={searchText}
          onChange={(e) => setSearchText(e.target.value)}
        />

        <Space className={styles.actions} wrap>
          <Button type="primary" icon={<PlusOutlined />} onClick={() => setCreateModalOpen(true)}>
            Khách hàng
          </Button>
          <Tooltip title="Tính năng gửi tin nhắn sẽ sớm có">
            <Button icon={<MailOutlined />} disabled>
              Gửi tin nhắn
            </Button>
          </Tooltip>
          <Tooltip title="Import khách hàng (đang chuẩn bị)">
            <Button icon={<ImportOutlined />} disabled>
              Import file
            </Button>
          </Tooltip>
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
          <div className={styles.filterGroup}>
            <span className={styles.filterLabel}>Loại khách hàng</span>
            <Segmented
              block
              size="middle"
              options={CUSTOMER_TYPES.map((t) => ({ label: t.label, value: t.value || 'ALL' }))}
              value={filters.customer_type || 'ALL'}
              onChange={(val) => handleTypeChange(val === 'ALL' ? null : val)}
            />
          </div>

          <Divider style={{ margin: '12px 0' }} />

          <div className={styles.filterGroup}>
            <span className={styles.filterLabel}>Giới tính</span>
            <Segmented
              block
              size="middle"
              options={GENDERS.map((g) => ({ label: g.label, value: g.value || 'ALL' }))}
              value={filters.gender || 'ALL'}
              onChange={(val) => handleGenderChange(val === 'ALL' ? null : val)}
            />
          </div>
        </Card>

        <div className={styles.tableStack}>
          <Card
            className={styles.tableCard}
            styles={{ body: { padding: 0 } }}
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
            />
          </Card>

          <Card title="Chi tiết khách hàng" className={styles.detailCard}>
            {renderDetail()}
          </Card>
        </div>
      </div>

      <Modal
        title="Thêm khách hàng"
        open={createModalOpen}
        onCancel={() => setCreateModalOpen(false)}
        onOk={handleCreate}
        confirmLoading={saving}
        okText="Lưu"
        cancelText="Hủy"
      >
        <Form layout="vertical" form={form} initialValues={{ customer_type: 'INDIVIDUAL' }}>
          <Form.Item
            label="Tên khách hàng"
            name="name"
            rules={[{ required: true, message: 'Vui lòng nhập tên khách hàng' }]}
          >
            <Input placeholder="Nhập tên" />
          </Form.Item>

          <Form.Item label="Số điện thoại" name="phone">
            <Input placeholder="Nhập số điện thoại" />
          </Form.Item>

          <Form.Item label="Loại khách hàng" name="customer_type">
            <Select
              aria-label="Loại khách hàng"
              options={CUSTOMER_TYPES.filter((t) => t.value).map((t) => ({
                label: t.label,
                value: t.value,
              }))}
            />
          </Form.Item>

          <Form.Item label="Giới tính" name="gender">
            <Select
              aria-label="Giới tính"
              allowClear
              options={GENDERS.filter((g) => g.value).map((g) => ({
                label: g.label,
                value: g.value,
              }))}
            />
          </Form.Item>
        </Form>
      </Modal>
    </div>
  );
};

export default CustomerListPage;
