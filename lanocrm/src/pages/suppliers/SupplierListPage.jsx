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

  const { items, pagination, loading, current, currentLoading } = useSelector(
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
        width: 180,
      },
      { key: 'email', title: 'Email', dataIndex: 'email', ellipsis: true },
      { key: 'address', title: 'Địa chỉ', dataIndex: 'address', ellipsis: true },
      { key: 'area', title: 'Khu vực', dataIndex: 'area', width: 200 },
      { key: 'ward', title: 'Phường/Xã', dataIndex: 'ward', width: 140 },
      { key: 'company', title: 'Công ty', dataIndex: 'company', width: 160 },
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
        width: 140,
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
        width: 140,
        render: (v) => <Tag color={statusColor[v] || 'default'}>{v === 'ACTIVE' ? 'Đang hoạt động' : 'Ngừng hoạt động'}</Tag>,
      },
    ],
    []
  );

  const [visibleCols, setVisibleCols] = useState(allColumns.map((c) => c.key));
  const columns = useMemo(
    () => allColumns.filter((col) => visibleCols.includes(col.key)),
    [allColumns, visibleCols]
  );

  // Fetch suppliers when filters change
  useEffect(() => {
    dispatch(fetchSuppliers(filters));
  }, [dispatch, filters]);

  // Auto pick first row + fetch detail
  useEffect(() => {
    if (!items || items.length === 0) {
      setSelectedId(null);
      return;
    }
    const stillExists = items.some((s) => s.id === selectedId);
    const pickId = stillExists ? selectedId : items[0].id;
    setSelectedId(pickId);
    dispatch(fetchSupplier(pickId));
  }, [items, selectedId, dispatch]);

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
                ghost={!!filters.status}
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
              <h2 className={styles.title}>Nhà cung cấp</h2>
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
                <Button type="primary" icon={<PlusOutlined />} onClick={() => message.info('Tính năng tạo nhà cung cấp sẽ kết nối backend sau.')}>
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
                <Tooltip title="Chế độ bảng">
                  <Button icon={<AppstoreOutlined />} />
                </Tooltip>
                <Tooltip title="Chế độ danh sách">
                  <Button icon={<UnorderedListOutlined />} />
                </Tooltip>
                <Tooltip title="Làm mới">
                  <Button icon={<ReloadOutlined />} onClick={() => dispatch(fetchSuppliers(filters))} />
                </Tooltip>
              </Space>
            </div>

            <div className={styles.tableWrapper}>
              <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8 }}>
                <div>Hiển thị {pagination.limit} dòng</div>
                <div className={styles.miniTag}>
                  {pagination.page} / {pagination.total_pages || 1} - Tổng {pagination.total} nhà cung cấp
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
                  showSizeChanger: false,
                  onChange: (page) => setFilters((prev) => ({ ...prev, page })),
                }}
                rowSelection={{
                  type: 'checkbox',
                  selectedRowKeys: selectedId ? [selectedId] : [],
                  onChange: (keys) => {
                    const id = keys[keys.length - 1];
                    if (id) setSelectedId(id);
                  },
                }}
                onRow={(record) => ({
                  onClick: () => {
                    setSelectedId(record.id);
                    dispatch(fetchSupplier(record.id));
                  },
                  className: record.id === selectedId ? 'ant-table-row-selected' : '',
                })}
                size="middle"
                scroll={{ x: 1100 }}
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SupplierListPage;
