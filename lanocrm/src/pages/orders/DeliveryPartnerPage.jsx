import React, { useEffect, useMemo, useRef, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import {
  Button,
  Card,
  Checkbox,
  DatePicker,
  Divider,
  Dropdown,
  Input,
  InputNumber,
  Modal,
  Radio,
  Select,
  Space,
  Table,
  Tabs,
  Tag,
  Tooltip,
  Typography,
  message,
} from 'antd';
import dayjs from 'dayjs';
import {
  PlusOutlined,
  UploadOutlined,
  DownloadOutlined,
  SettingOutlined,
  QuestionCircleOutlined,
  ColumnHeightOutlined,
  SearchOutlined,
} from '@ant-design/icons';
import {
  DELIVERY_PARTNER_COLUMNS,
  DELIVERY_PARTNER_GROUPS,
  DELIVERY_PARTNER_STATUSES,
  DELIVERY_PARTNER_TABS,
  DELIVERY_PARTNER_PAGE_SIZES,
  defaultVisibleColumns,
} from '../../constants/deliveryPartners';
import {
  fetchDeliveryPartners,
  setDeliveryPartnerFilters,
  setDeliveryPartnerPage,
  setDeliveryPartnerSort,
  setVisibleDeliveryColumns,
} from '../../store/slices/deliveryPartnerSlice';
import styles from './DeliveryPartnerPage.module.css';

const { RangePicker } = DatePicker;
const currency = (value) =>
  new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(
    Number(value || 0)
  );

const DeliveryPartnerPage = () => {
  const dispatch = useDispatch();
  const { items, pagination, filters, summary, loading, visibleColumns } = useSelector(
    (s) => s.deliveryPartners
  );

  const [searchText, setSearchText] = useState(filters.search || '');
  const [selectedRowKeys, setSelectedRowKeys] = useState([]);
  const [createModalOpen, setCreateModalOpen] = useState(false);
  const [newPartner, setNewPartner] = useState({
    name: '',
    phone: '',
    email: '',
    group: null,
    type: 'individual',
    note: '',
  });
  const [columnMenuOpen, setColumnMenuOpen] = useState(false);
  const [timeMode, setTimeMode] = useState('all');
  const fileInputRef = useRef(null);
  const [expandedRowKeys, setExpandedRowKeys] = useState([]);
  const [integratedTab, setIntegratedTab] = useState('info');
  const [reconcileFilter, setReconcileFilter] = useState({ search: '', status: 'all' });
  const [shippingFilter, setShippingFilter] = useState({ search: '', partner: 'all' });
  const [reconcileModalOpen, setReconcileModalOpen] = useState(false);
  const [selectedVoucher, setSelectedVoucher] = useState(null);
  const [selectedOrder, setSelectedOrder] = useState(null);
  const [invoiceModalOpen, setInvoiceModalOpen] = useState(false);
  const [selectedShipment, setSelectedShipment] = useState(null);
  const [shipmentModalOpen, setShipmentModalOpen] = useState(false);

  useEffect(() => {
    dispatch(fetchDeliveryPartners());
  }, [dispatch, filters]);

  useEffect(() => {
    const timer = setTimeout(() => {
      dispatch(setDeliveryPartnerFilters({ search: searchText }));
    }, 350);
    return () => clearTimeout(timer);
  }, [searchText, dispatch]);

  const tableColumns = useMemo(() => {
    const baseColumns = [
      {
        title: 'Mã đối tác',
        dataIndex: 'code',
        key: 'code',
        width: 130,
        sorter: true,
        render: (code, record) => (
          <Space size={4}>
            <span className={styles.code}>{code}</span>
            {record.integrated && <Tag color="blue">KiotViet</Tag>}
          </Space>
        ),
      },
      {
        title: 'Tên đối tác',
        dataIndex: 'name',
        key: 'name',
        width: 200,
        sorter: true,
        render: (text) => <span className={styles.name}>{text}</span>,
      },
      {
        title: 'Điện thoại',
        dataIndex: 'phone',
        key: 'phone',
        width: 140,
      },
      {
        title: 'Email',
        dataIndex: 'email',
        key: 'email',
        width: 180,
        render: (value) => value || '—',
      },
      {
        title: 'Tổng đơn hàng',
        dataIndex: 'total_orders',
        key: 'total_orders',
        align: 'right',
        sorter: true,
        render: (value) => value?.toLocaleString('vi-VN'),
      },
      {
        title: 'Cần thu hộ (COD)',
        dataIndex: 'cod_collect',
        key: 'cod_collect',
        align: 'right',
        sorter: true,
        render: (value) => currency(value),
      },
      {
        title: 'Còn cần thu (COD)',
        dataIndex: 'cod_remain',
        key: 'cod_remain',
        align: 'right',
        sorter: true,
        render: (value) => currency(value),
      },
      {
        title: 'Tổng trọng lượng',
        dataIndex: 'total_weight',
        key: 'total_weight',
        align: 'right',
        sorter: true,
        render: (value) => `${value?.toLocaleString('vi-VN')} kg`,
      },
      {
        title: 'Nợ cần trả hiện tại',
        dataIndex: 'current_debt',
        key: 'current_debt',
        align: 'right',
        sorter: true,
        render: (value) => (
          <span className={Number(value) < 0 ? styles.negative : ''}>{currency(value)}</span>
        ),
      },
      {
        title: 'Tổng phí giao hàng',
        dataIndex: 'total_shipping_fee',
        key: 'total_shipping_fee',
        align: 'right',
        sorter: true,
        render: (value) => currency(value),
      },
      {
        title: 'Trạng thái',
        dataIndex: 'status',
        key: 'status',
        width: 130,
        render: (value) => (
          <Tag color={value === 'active' ? 'green' : 'volcano'}>
            {value === 'active' ? 'Đang hoạt động' : 'Ngừng hoạt động'}
          </Tag>
        ),
      },
    ];

    return baseColumns.filter((col) => visibleColumns.includes(col.key));
  }, [visibleColumns]);

  const columnMenuItems = DELIVERY_PARTNER_COLUMNS.map((col) => ({
    key: col.key,
    label: (
      <Checkbox
        checked={visibleColumns.includes(col.key)}
        onChange={(e) => {
          const next = e.target.checked
            ? Array.from(new Set([...visibleColumns, col.key]))
            : visibleColumns.filter((c) => c !== col.key);
          dispatch(setVisibleDeliveryColumns(next.length ? next : defaultVisibleColumns));
        }}
      >
        {col.label}
      </Checkbox>
    ),
  }));

  const handleTableChange = (pager, _filters, sorter) => {
    if (pager) {
      dispatch(setDeliveryPartnerPage({ page: pager.current, limit: pager.pageSize }));
    }
    if (sorter?.field) {
      dispatch(
        setDeliveryPartnerSort({
          sort_by: sorter.field,
          sort_order: sorter.order || 'ascend',
        })
      );
    }
  };

  const handleExport = () => {
    if (!items?.length) {
      message.info('Không có dữ liệu để xuất');
      return;
    }
    const headers = visibleColumns;
    const csv = [headers.join(',')].concat(
      items.map((row) =>
        headers
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
    link.download = `delivery-partners-${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    message.success(`Đã xuất ${items.length} đối tác`);
  };

  const handleImport = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    message.success(`Đã tải lên ${file.name} (mock)`);
    event.target.value = '';
  };

  const handleCreate = () => {
    if (!newPartner.name) {
      message.warning('Vui lòng nhập tên đối tác');
      return;
    }
    message.success('Đã lưu đối tác (mock)');
    setCreateModalOpen(false);
    setNewPartner({ name: '', phone: '', email: '', group: null, type: 'individual', note: '' });
  };

  const summaryCards = useMemo(
    () => [
      { label: 'Tổng đơn hàng', value: summary.total_orders?.toLocaleString('vi-VN') },
      { label: 'Tổng nợ', value: currency(summary.total_debt) },
      { label: 'Tổng phí giao hàng', value: currency(summary.total_fee) },
    ],
    [summary]
  );

  const paginationConfig = {
    current: pagination.page,
    pageSize: pagination.limit,
    total: pagination.total,
    showSizeChanger: true,
    pageSizeOptions: DELIVERY_PARTNER_PAGE_SIZES.map(String),
    showTotal: (total, range) => `${range[0]} - ${range[1]} trong ${total} đối tác`,
  };

  const integratedPartners = useMemo(() => items.filter((i) => i.integrated), [items]);

  const shippingRows = useMemo(() => {
    return integratedPartners.flatMap((p) =>
      (p.shipping_history || []).map((row) => ({ ...row, partner: p.name, partner_code: p.code }))
    );
  }, [integratedPartners]);

  const reconcileRows = useMemo(() => {
    return integratedPartners.flatMap((p) =>
      (p.payables || []).map((row) => ({ ...row, partner: p.name, partner_code: p.code }))
    );
  }, [integratedPartners]);

  const renderSidebarContent = () => {
    const tabsHeader = (
      <Tabs
        className={styles.tabs}
        activeKey={filters.tab}
        onChange={(key) => dispatch(setDeliveryPartnerFilters({ tab: key }))}
        items={DELIVERY_PARTNER_TABS.map((t) => ({ key: t.value, label: t.label }))}
      />
    );

    if (filters.tab === 'integrated') {
      return (
        <Card className={styles.filterCard} styles={{ body: { padding: 16 } }}>
          {tabsHeader}
          <div className={styles.promoBlock}>
            <div className={styles.promoIllustration}>
              <span role="img" aria-label="delivery">🚚</span>
            </div>
            <Typography.Title level={4} style={{ margin: '4px 0', textAlign: 'center' }}>
              Nhanh chóng, thuận tiện
            </Typography.Title>
            <Typography.Paragraph type="secondary" style={{ textAlign: 'center', margin: 4 }}>
              Khi sử dụng dịch vụ cửa hàng vận chuyển có tích hợp với LANO CRM.
            </Typography.Paragraph>
            <Typography.Link style={{ textAlign: 'center' }}>Chi tiết xem tại đây</Typography.Link>
          </div>
        </Card>
      );
    }

    return (
      <Card className={styles.filterCard} styles={{ body: { padding: 12 } }}>
        {tabsHeader}

        <div className={styles.filterBlock}>
          <div className={styles.filterLabelRow}>
            <span>Nhóm ĐTGH</span>
            <Typography.Link> Tạo mới</Typography.Link>
          </div>
          <Select
            value={filters.group}
            options={DELIVERY_PARTNER_GROUPS}
            style={{ width: '100%' }}
            onChange={(v) => dispatch(setDeliveryPartnerFilters({ group: v }))}
          />
        </div>

        <Divider className={styles.divider} />
        <div className={styles.filterBlock}>
          <div className={styles.filterLabel}>Tổng phí giao hàng</div>
          <Space orientation="vertical" size={8} style={{ width: '100%' }}>
            <InputNumber
              placeholder="Từ"
              value={filters.fee_from}
              onChange={(v) => dispatch(setDeliveryPartnerFilters({ fee_from: v }))}
              style={{ width: '100%' }}
              min={0}
              step={10000}
            />
            <InputNumber
              placeholder="Tới"
              value={filters.fee_to}
              onChange={(v) => dispatch(setDeliveryPartnerFilters({ fee_to: v }))}
              style={{ width: '100%' }}
              min={0}
              step={10000}
            />
          </Space>
        </div>

        <Divider className={styles.divider} />
        <div className={styles.filterBlock}>
          <div className={styles.filterLabel}>Thời gian</div>
          <Radio.Group
            value={timeMode}
            onChange={(e) => {
              const mode = e.target.value;
              setTimeMode(mode);
              if (mode === 'all') {
                dispatch(setDeliveryPartnerFilters({ date_from: null, date_to: null }));
              }
            }}
            style={{ display: 'flex', flexDirection: 'column', gap: 8 }}
          >
            <Radio value="all">Toàn thời gian</Radio>
            <Radio value="custom">Tùy chỉnh</Radio>
          </Radio.Group>
          {timeMode === 'custom' ? (
            <RangePicker
              style={{ width: '100%', marginTop: 8 }}
              value=
                {filters.date_from && filters.date_to ? [dayjs(filters.date_from), dayjs(filters.date_to)] : null}
              onChange={(dates) => {
                if (!dates) {
                  dispatch(setDeliveryPartnerFilters({ date_from: null, date_to: null }));
                  return;
                }
                dispatch(
                  setDeliveryPartnerFilters({
                    date_from: dates[0]?.format('YYYY-MM-DD') || null,
                    date_to: dates[1]?.format('YYYY-MM-DD') || null,
                  })
                );
              }}
              format="DD/MM/YYYY"
            />
          ) : null}
        </div>

        <Divider className={styles.divider} />
        <div className={styles.filterBlock}>
          <div className={styles.filterLabel}>Nợ hiện tại</div>
          <Space orientation="vertical" size={8} style={{ width: '100%' }}>
            <InputNumber
              placeholder="Từ"
              value={filters.debt_from}
              onChange={(v) => dispatch(setDeliveryPartnerFilters({ debt_from: v }))}
              style={{ width: '100%' }}
              step={50000}
            />
            <InputNumber
              placeholder="Tới"
              value={filters.debt_to}
              onChange={(v) => dispatch(setDeliveryPartnerFilters({ debt_to: v }))}
              style={{ width: '100%' }}
              step={50000}
            />
          </Space>
        </div>

        <Divider className={styles.divider} />
        <div className={styles.filterBlock}>
          <div className={styles.filterLabel}>Trạng thái</div>
          <Radio.Group
            buttonStyle="solid"
            value={filters.status}
            onChange={(e) => dispatch(setDeliveryPartnerFilters({ status: e.target.value }))}
            style={{ display: 'flex', gap: 8 }}
          >
            {DELIVERY_PARTNER_STATUSES.map((status) => (
              <Radio.Button key={status.value} value={status.value}>
                {status.label}
              </Radio.Button>
            ))}
          </Radio.Group>
        </div>
      </Card>
    );
  };

  const statusTag = (status) => {
    const map = {
      shipping: { color: 'geekblue', label: 'Đang giao hàng' },
      delivered: { color: 'green', label: 'Giao thành công' },
      processing: { color: 'gold', label: 'Chờ xử lý' },
      pending: { color: 'gold', label: 'Chờ xác nhận' },
      confirmed: { color: 'blue', label: 'Đã xác nhận' },
    };
    const found = map[status] || { color: 'default', label: status };
    return <Tag color={found.color}>{found.label}</Tag>;
  };

  const expandedRowRender = (record) => {
    const info = record || {};
    const shippingColumns = [
      { title: 'Mã hóa đơn', dataIndex: 'order_code', key: 'order_code' },
      { title: 'Mã vận đơn', dataIndex: 'tracking_code', key: 'tracking_code' },
      { title: 'Thời gian', dataIndex: 'time', key: 'time' },
      {
        title: 'Giá trị hợp đồng',
        dataIndex: 'contract_value',
        key: 'contract_value',
        align: 'right',
        render: (v) => currency(v),
      },
      {
        title: 'Còn cần thu (COD)',
        dataIndex: 'cod_remain',
        key: 'cod_remain',
        align: 'right',
        render: (v) => currency(v),
      },
      {
        title: 'Phí trả ĐTGH',
        dataIndex: 'fee',
        key: 'fee',
        align: 'right',
        render: (v) => currency(v),
      },
      {
        title: 'Trạng thái',
        dataIndex: 'status',
        key: 'status',
        render: statusTag,
      },
    ];

    const payableColumns = [
      { title: 'Mã phiếu', dataIndex: 'voucher_code', key: 'voucher_code' },
      { title: 'Thời gian', dataIndex: 'time', key: 'time' },
      { title: 'Loại', dataIndex: 'type', key: 'type' },
      {
        title: 'Giá trị',
        dataIndex: 'value',
        key: 'value',
        align: 'right',
        render: (v) => currency(v),
      },
      {
        title: 'Nợ cần trả ĐT',
        dataIndex: 'partner_debt',
        key: 'partner_debt',
        align: 'right',
        render: (v) => currency(v),
      },
    ];

    return (
      <div className={styles.expandWrapper}>
        <Tabs
          defaultActiveKey="info"
          items={[
            {
              key: 'info',
              label: 'Thông tin',
              children: (
                <div className={styles.infoGrid}>
                  <div className={styles.infoTitle}>{info.name} <span className={styles.code}>{info.code}</span></div>
                  <div className={styles.infoRow}><strong>Nhóm đối tác:</strong> <span>{info.group || 'Chưa có'}</span></div>
                  <div className={styles.infoRow}><strong>Điện thoại:</strong> <span>{info.phone || 'Chưa có'}</span></div>
                  <div className={styles.infoRow}><strong>Email:</strong> <span>{info.email || 'Chưa có'}</span></div>
                  <div className={styles.infoRow}><strong>Địa chỉ:</strong> <span>{info.address || 'Chưa có'}</span></div>
                  <div className={styles.infoRow}><strong>Ghi chú:</strong> <span>{info.note || 'Chưa có'}</span></div>
                  <div className={styles.infoActions}>
                    <Button icon={<ColumnHeightOutlined />} ghost type="primary">Chỉnh sửa</Button>
                    <Button icon={<DownloadOutlined />} danger ghost>Ngừng hoạt động</Button>
                    <Button icon={<ColumnHeightOutlined />} danger type="text">Xóa</Button>
                  </div>
                </div>
              ),
            },
            {
              key: 'shipping',
              label: 'Lịch sử giao hàng',
              children: (
                <div>
                  <Table
                    rowKey={(r) => r.order_code + r.time}
                    size="small"
                    dataSource={info.shipping_history || []}
                    columns={shippingColumns}
                    pagination={{ pageSize: 10, showSizeChanger: false, showTotal: (t, r) => `${r[0]} - ${r[1]} trong ${t} dòng` }}
                  />
                  <div className={styles.expandActions}>
                    <Button icon={<DownloadOutlined />}>Xuất file</Button>
                    <Button type="primary">Cập nhật giao hàng</Button>
                  </div>
                </div>
              ),
            },
            {
              key: 'payable',
              label: 'Phí cần trả đối tác GH',
              children: (
                <div>
                  <Table
                    rowKey={(r) => r.voucher_code + r.time}
                    size="small"
                    dataSource={info.payables || []}
                    columns={payableColumns}
                    pagination={{ pageSize: 10, showSizeChanger: false, showTotal: (t, r) => `${r[0]} - ${r[1]} trong ${t} dòng` }}
                  />
                  <div className={styles.expandActions}>
                    <Button icon={<DownloadOutlined />}>Xuất file</Button>
                    <Button type="primary">Điều chỉnh</Button>
                  </div>
                </div>
              ),
            },
          ]}
        />
      </div>
    );
  };

  const renderIntegratedContent = () => {
    const infoColumns = [
      { title: 'Mã đối tác', dataIndex: 'code', key: 'code', width: 140 },
      { title: 'Tên đối tác', dataIndex: 'name', key: 'name', render: (t) => <span className={styles.name}>{t}</span> },
      { title: 'Tổng đơn hàng', dataIndex: 'total_orders', key: 'total_orders', align: 'right', render: (v) => v?.toLocaleString('vi-VN') },
      { title: 'Cần thu hộ (COD)', dataIndex: 'cod_collect', key: 'cod_collect', align: 'right', render: (v) => currency(v) },
      { title: 'Còn cần thu (COD)', dataIndex: 'cod_remain', key: 'cod_remain', align: 'right', render: (v) => currency(v) },
      { title: 'Tổng phí giao hàng', dataIndex: 'total_shipping_fee', key: 'total_shipping_fee', align: 'right', render: (v) => currency(v) },
      { title: 'Còn cần trả ĐTGH', dataIndex: 'current_debt', key: 'current_debt', align: 'right', render: (v) => currency(v) },
    ];

    const shippingCols = [
      {
        title: 'Mã hóa đơn',
        dataIndex: 'order_code',
        key: 'order_code',
        render: (v, row) => (
          <Button
            type="link"
            onClick={() => {
              setSelectedOrder(row);
              setInvoiceModalOpen(true);
            }}
          >
            {v}
          </Button>
        ),
      },
      { title: 'Mã ĐTGH', dataIndex: 'partner_code', key: 'partner_code' },
      {
        title: 'Mã vận đơn',
        dataIndex: 'tracking_code',
        key: 'tracking_code',
        render: (v, row) => (
          <Button
            type="link"
            onClick={() => {
              setSelectedShipment(row);
              setShipmentModalOpen(true);
            }}
          >
            {v || '---'}
          </Button>
        ),
      },
      { title: 'Thời gian', dataIndex: 'time', key: 'time' },
      { title: 'Giá trị hợp đồng', dataIndex: 'contract_value', key: 'contract_value', align: 'right', render: (v) => currency(v) },
      { title: 'Còn cần thu (COD)', dataIndex: 'cod_remain', key: 'cod_remain', align: 'right', render: (v) => currency(v) },
      { title: 'Phí trả ĐTGH', dataIndex: 'fee', key: 'fee', align: 'right', render: (v) => currency(v) },
      { title: 'Trạng thái', dataIndex: 'status', key: 'status', render: statusTag },
    ];

    const reconcileCols = [
      { title: 'Mã phiếu', dataIndex: 'voucher_code', key: 'voucher_code' },
      { title: 'Thời gian tạo', dataIndex: 'time', key: 'time' },
      { title: 'Công nợ', dataIndex: 'partner_debt', key: 'partner_debt', align: 'right', render: (v) => currency(v) },
      {
        title: 'Trạng thái',
        dataIndex: 'status',
        key: 'status',
        render: (v) => statusTag(v || 'pending'),
      },
      {
        title: 'Thao tác',
        key: 'action',
        render: (_, row) => (
          <Button
            type="primary"
            size="small"
            onClick={() => {
              setSelectedVoucher(row);
              setReconcileModalOpen(true);
            }}
          >
            Xác nhận
          </Button>
        ),
      },
    ];

    const filteredShipping = shippingRows.filter((row) => {
      if (shippingFilter.partner !== 'all' && row.partner_code !== shippingFilter.partner) return false;
      if (shippingFilter.search && !row.order_code.toLowerCase().includes(shippingFilter.search.toLowerCase())) return false;
      return true;
    });

    const filteredReconcile = reconcileRows.filter((row) => {
      if (reconcileFilter.status !== 'all' && (row.status || 'pending') !== reconcileFilter.status) return false;
      if (reconcileFilter.search && !row.voucher_code.toLowerCase().includes(reconcileFilter.search.toLowerCase())) return false;
      return true;
    });

    const infoTotal = integratedPartners.reduce(
      (acc, p) => {
        acc.orders += Number(p.total_orders || 0);
        acc.codCollect += Number(p.cod_collect || 0);
        acc.codRemain += Number(p.cod_remain || 0);
        acc.fee += Number(p.total_shipping_fee || 0);
        acc.debt += Number(p.current_debt || 0);
        return acc;
      },
      { orders: 0, codCollect: 0, codRemain: 0, fee: 0, debt: 0 }
    );

    const infoTable = (
      <Table
        rowKey="code"
        dataSource={integratedPartners}
        columns={infoColumns}
        pagination={false}
        summary={() => (
          <Table.Summary.Row>
            <Table.Summary.Cell index={0}> </Table.Summary.Cell>
            <Table.Summary.Cell index={1} className={styles.summaryCell}>Tổng</Table.Summary.Cell>
            <Table.Summary.Cell index={2} align="right" className={styles.summaryCell}>{infoTotal.orders.toLocaleString('vi-VN')}</Table.Summary.Cell>
            <Table.Summary.Cell index={3} align="right" className={styles.summaryCell}>{currency(infoTotal.codCollect)}</Table.Summary.Cell>
            <Table.Summary.Cell index={4} align="right" className={styles.summaryCell}>{currency(infoTotal.codRemain)}</Table.Summary.Cell>
            <Table.Summary.Cell index={5} align="right" className={styles.summaryCell}>{currency(infoTotal.fee)}</Table.Summary.Cell>
            <Table.Summary.Cell index={6} align="right" className={styles.summaryCell}>{currency(infoTotal.debt)}</Table.Summary.Cell>
          </Table.Summary.Row>
        )}
      />
    );

    const shippingFilters = (
      <Space className={styles.integratedFilters} wrap>
        <Input
          placeholder="Tìm mã hóa đơn"
          value={shippingFilter.search}
          onChange={(e) => setShippingFilter((s) => ({ ...s, search: e.target.value }))}
          style={{ width: 220 }}
        />
        <Select
          value={shippingFilter.partner}
          style={{ width: 180 }}
          options={[{ value: 'all', label: 'Tất cả' }, ...integratedPartners.map((p) => ({ value: p.code, label: p.name }))]}
          onChange={(v) => setShippingFilter((s) => ({ ...s, partner: v }))}
        />
      </Space>
    );

    const reconcileFilters = (
      <Space className={styles.integratedFilters} wrap>
        <Input
          placeholder="Tìm mã phiếu"
          value={reconcileFilter.search}
          onChange={(e) => setReconcileFilter((s) => ({ ...s, search: e.target.value }))}
          style={{ width: 220 }}
        />
        <Select
          value={reconcileFilter.status}
          style={{ width: 160 }}
          options={[
            { value: 'all', label: 'Tất cả' },
            { value: 'pending', label: 'Chờ xác nhận' },
            { value: 'confirmed', label: 'Đã xác nhận' },
          ]}
          onChange={(v) => setReconcileFilter((s) => ({ ...s, status: v }))}
        />
      </Space>
    );

    const shippingTable = (
      <>
        {shippingFilters}
        <Table
          rowKey={(r) => r.order_code + r.time}
          dataSource={filteredShipping}
          columns={shippingCols}
          pagination={{ pageSize: 15, showSizeChanger: false, showTotal: (t, r) => `${r[0]} - ${r[1]} trong ${t} hóa đơn` }}
          size="small"
        />
        <div className={styles.expandActions}>
          <Button icon={<DownloadOutlined />}>Xuất file</Button>
          <Button type="primary">Cập nhật giao hàng</Button>
        </div>
      </>
    );

    const reconcileTable = (
      <>
        {reconcileFilters}
        <Table
          rowKey={(r) => r.voucher_code + r.time}
          dataSource={filteredReconcile}
          columns={reconcileCols}
          pagination={{ pageSize: 15, showSizeChanger: false, showTotal: (t, r) => `${r[0]} - ${r[1]} trong ${t} phiếu` }}
          size="small"
          onRow={(row) => ({
            onClick: () => {
              setSelectedVoucher(row);
              setReconcileModalOpen(true);
            },
            style: { cursor: 'pointer' },
          })}
        />
        <div className={styles.expandActions}>
          <Button icon={<DownloadOutlined />}>Xuất file</Button>
          <Button type="primary">Xác nhận</Button>
        </div>
      </>
    );

    return (
      <div className={styles.integratedWrapper}>
        <Tabs
          activeKey={integratedTab}
          onChange={setIntegratedTab}
          items={[
            { key: 'info', label: 'Thông tin', children: infoTable },
            { key: 'shipping', label: 'Lịch sử giao hàng', children: shippingTable },
            { key: 'reconcile', label: 'Lịch sử đối soát', children: reconcileTable },
          ]}
        />
      </div>
    );
  };

  const renderReconcileModal = () => {
    if (!selectedVoucher) return null;
    const detailColumns = [
      { title: 'Mã hóa đơn', dataIndex: 'order_code', key: 'order_code', render: (v) => <a>{v}</a> },
      { title: 'Mã vận đơn', dataIndex: 'tracking_code', key: 'tracking_code' },
      { title: 'Đối tác giao hàng', dataIndex: 'partner', key: 'partner' },
      { title: 'Ngày tạo', dataIndex: 'created_at', key: 'created_at' },
      { title: 'Ngày hoàn thành', dataIndex: 'completed_at', key: 'completed_at' },
      { title: 'Trạng thái giao', dataIndex: 'ship_status', key: 'ship_status', render: statusTag },
      { title: 'COD đã ứng', dataIndex: 'cod_paid', key: 'cod_paid', align: 'right', render: (v) => currency(v) },
      { title: 'COD thực tế', dataIndex: 'cod_actual', key: 'cod_actual', align: 'right', render: (v) => currency(v) },
      { title: 'COD đối soát', dataIndex: 'cod_reconcile', key: 'cod_reconcile', align: 'right', render: (v) => currency(v) },
    ];

    const detailSummary = (selectedVoucher.detail || []).reduce(
      (acc, d) => {
        acc.codPaid += Number(d.cod_paid || 0);
        acc.codActual += Number(d.cod_actual || 0);
        acc.codRec += Number(d.cod_reconcile || 0);
        return acc;
      },
      { codPaid: 0, codActual: 0, codRec: 0 }
    );

    return (
      <Modal
        title="Chi tiết phiếu"
        open={reconcileModalOpen}
        onCancel={() => setReconcileModalOpen(false)}
        footer={null}
        width={980}
      >
        <div className={styles.voucherInfo}>
          <div><strong>Mã phiếu:</strong> <a>{selectedVoucher.voucher_code}</a></div>
          <div><strong>Thời gian:</strong> {selectedVoucher.time}</div>
          <div><strong>Trạng thái:</strong> {statusTag(selectedVoucher.status || 'pending')}</div>
          <div><strong>Số tài khoản:</strong> {selectedVoucher.bank_account || '—'}</div>
          <div><strong>COD kỳ này (1):</strong> {currency(selectedVoucher.cod_this || selectedVoucher.partner_debt)}</div>
          <div><strong>Phí phát sinh kỳ này (2):</strong> {currency(selectedVoucher.fee_this || 25750)}</div>
          <div><strong>Phát sinh kỳ trước (3):</strong> {currency(selectedVoucher.prev_fee || 0)}</div>
          <div><strong>Số dư TK trả phí đầu kỳ (4):</strong> {currency(selectedVoucher.open_balance || 0)}</div>
          <div><strong>Số dư TK trả phí cuối kỳ (5):</strong> {currency(selectedVoucher.close_balance || 0)}</div>
          <div><strong>Thanh toán kỳ này (6):</strong> {currency(selectedVoucher.payment_this || (selectedVoucher.partner_debt || 0))}</div>
          <Input.TextArea
            placeholder="Ghi chú..."
            rows={3}
            defaultValue={selectedVoucher.note || ''}
            style={{ gridColumn: 'span 2' }}
          />
        </div>

        <div className={styles.voucherDetails}>Vận đơn kỳ này</div>
        <Table
          rowKey={(r) => r.order_code + r.tracking_code}
          dataSource={selectedVoucher.detail || []}
          columns={detailColumns}
          pagination={false}
          size="small"
          summary={() => (
            <Table.Summary.Row>
              <Table.Summary.Cell index={0}>Tổng</Table.Summary.Cell>
              <Table.Summary.Cell index={1} />
              <Table.Summary.Cell index={2} />
              <Table.Summary.Cell index={3} />
              <Table.Summary.Cell index={4} />
              <Table.Summary.Cell index={5} />
              <Table.Summary.Cell index={6} align="right">{currency(detailSummary.codPaid)}</Table.Summary.Cell>
              <Table.Summary.Cell index={7} align="right">{currency(detailSummary.codActual)}</Table.Summary.Cell>
              <Table.Summary.Cell index={8} align="right">{currency(detailSummary.codRec)}</Table.Summary.Cell>
            </Table.Summary.Row>
          )}
        />

        <div className={styles.modalActions}>
          <Space>
            <Button type="primary">Xác nhận</Button>
            <Button icon={<DownloadOutlined />}>Xuất file</Button>
            <Button onClick={() => setReconcileModalOpen(false)}>Bỏ qua</Button>
          </Space>
        </div>
      </Modal>
    );
  };

  const renderInvoiceModal = () => {
    if (!selectedOrder) return null;
    const lineItems = selectedOrder.items || [
      {
        sku: 'KT195',
        name: 'Túi nam đeo chéo lano da bò cao cấp thời trang tiện lợi KT195',
        qty: 1,
        price: selectedOrder.contract_value || 1250000,
        discount: 0,
      },
    ];

    const totals = lineItems.reduce(
      (acc, i) => {
        acc.subtotal += i.price * i.qty;
        acc.pay += (selectedOrder.cod_actual || i.price * i.qty) / 2; // mock
        return acc;
      },
      { subtotal: 0, pay: 0 }
    );

    const columns = [
      { title: 'Mã hàng', dataIndex: 'sku', key: 'sku', render: (v) => <a>{v}</a> },
      { title: 'Tên hàng', dataIndex: 'name', key: 'name' },
      { title: 'Số lượng', dataIndex: 'qty', key: 'qty', align: 'right' },
      { title: 'Đơn giá', dataIndex: 'price', key: 'price', align: 'right', render: (v) => currency(v) },
      { title: 'Giảm giá', dataIndex: 'discount', key: 'discount', align: 'right', render: (v) => currency(v) },
      { title: 'Giá bán', key: 'price2', align: 'right', render: (_, r) => currency(r.price - r.discount) },
      { title: 'Thành tiền', key: 'total', align: 'right', render: (_, r) => currency((r.price - r.discount) * r.qty) },
    ];

    return (
      <Modal
        title="Hóa đơn"
        open={invoiceModalOpen}
        width={960}
        footer={null}
        onCancel={() => setInvoiceModalOpen(false)}
      >
        <div className={styles.invoiceHeader}>
          <div>
            <div className={styles.invoiceTitleRow}>
              <span className={styles.customerName}>Huy Nguyễn</span>
              <a>{selectedOrder.order_code}</a>
              {statusTag('processing')}
            </div>
            <div className={styles.invoiceMeta}>Người tạo: Chị Phượng Anh</div>
            <div className={styles.invoiceMeta}>Ngày bán: {selectedOrder.time}</div>
          </div>
          <div className={styles.invoiceBranch}>Lano - HN</div>
        </div>

        <Table
          rowKey={(r) => r.sku}
          dataSource={lineItems}
          columns={columns}
          pagination={false}
          size="small"
        />

        <div className={styles.invoiceTotals}>
          <div>Tổng tiền hàng (1)</div>
          <div>{currency(totals.subtotal)}</div>
          <div>Giảm giá hóa đơn</div>
          <div>{currency(0)}</div>
          <div>Khách cần trả</div>
          <div>{currency(totals.subtotal)}</div>
          <div>Khách đã trả</div>
          <div>{currency(totals.pay)}</div>
        </div>

        <div className={styles.modalActions}>
          <Space>
            <Button type="primary">Mở phiếu</Button>
          </Space>
        </div>
      </Modal>
    );
  };

  const renderShipmentModal = () => {
    if (!selectedShipment) return null;
    return (
      <Modal
        title="Vận đơn"
        open={shipmentModalOpen}
        width={720}
        footer={null}
        onCancel={() => setShipmentModalOpen(false)}
      >
        <div className={styles.voucherInfo}>
          <div><strong>Mã vận đơn:</strong> {selectedShipment.tracking_code}</div>
          <div><strong>Đối tác:</strong> {selectedShipment.partner}</div>
          <div><strong>Trạng thái:</strong> {statusTag(selectedShipment.status)}</div>
          <div><strong>Thời gian:</strong> {selectedShipment.time}</div>
          <div><strong>COD:</strong> {currency(selectedShipment.cod_remain || selectedShipment.cod_actual)}</div>
          <div><strong>Phí trả ĐTGH:</strong> {currency(selectedShipment.fee)}</div>
        </div>
        <div className={styles.modalActions}>
          <Space>
            <Button type="primary">Xem vận đơn</Button>
          </Space>
        </div>
      </Modal>
    );
  };

  return (
    <div className={styles.page}>
      <div className={styles.headerRow}>
        <Input
          allowClear
          prefix={<SearchOutlined />}
          placeholder="Theo mã, tên, số điện thoại"
          value={searchText}
          onChange={(e) => setSearchText(e.target.value)}
          className={styles.search}
          size="large"
        />
        <Space size="small" wrap>
          <Button type="default" icon={<PlusOutlined />} onClick={() => setCreateModalOpen(true)}>
            Đối tác giao hàng
          </Button>
          <Button icon={<UploadOutlined />} onClick={() => fileInputRef.current?.click()}>
            Import file
          </Button>
          <Button icon={<DownloadOutlined />} onClick={handleExport}>
            Xuất file
          </Button>
          <Dropdown
            open={columnMenuOpen}
            onOpenChange={setColumnMenuOpen}
            menu={{ items: columnMenuItems }}
            placement="bottomRight"
          >
            <Button icon={<ColumnHeightOutlined />}>Ẩn hiện cột</Button>
          </Dropdown>
          <Tooltip title="Cấu hình giao hàng">
            <Button icon={<SettingOutlined />} href="/man/#/Settings?SettingType=delivery" target="_blank" />
          </Tooltip>
          <Tooltip title="Xem hướng dẫn">
            <Button icon={<QuestionCircleOutlined />} href="https://support.kiotviet.vn" target="_blank" />
          </Tooltip>
        </Space>
      </div>

      {filters.tab === 'integrated' ? (
        <div className={styles.integratedLayout}>
          <div className={styles.integratedSidebar}>{renderSidebarContent()}</div>
          <div className={styles.integratedContent}>{renderIntegratedContent()}</div>
        </div>
      ) : (
        <div className={styles.layout}>
          {renderSidebarContent()}

          <div className={styles.tableArea}>
            <div className={styles.summaryBar}>
              {summaryCards.map((item) => (
                <div key={item.label} className={styles.summaryItem}>
                  <div className={styles.summaryLabel}>{item.label}</div>
                  <div className={styles.summaryValue}>{item.value}</div>
                </div>
              ))}
            </div>

            <Table
              rowKey="id"
              loading={loading}
              dataSource={items}
              columns={tableColumns}
              size="middle"
              rowSelection={{ selectedRowKeys, onChange: setSelectedRowKeys }}
              pagination={paginationConfig}
              onChange={handleTableChange}
              className={styles.table}
              expandable={{
                expandedRowRender,
                expandedRowKeys,
                onExpand: (expanded, record) => {
                  setExpandedRowKeys(expanded ? [record.id] : []);
                },
                expandRowByClick: true,
              }}
            />
          </div>
        </div>
      )}

      {renderReconcileModal()}
      {renderInvoiceModal()}
      {renderShipmentModal()}

      <Modal
        title="Tạo đối tác giao hàng"
        open={createModalOpen}
        onCancel={() => setCreateModalOpen(false)}
        onOk={handleCreate}
        okText="Lưu"
        destroyOnHidden
        width={720}
      >
        <div className={styles.modalGrid}>
          <Input
            placeholder="Tên đối tác (bắt buộc)"
            value={newPartner.name}
            onChange={(e) => setNewPartner((p) => ({ ...p, name: e.target.value }))}
          />
          <Input
            placeholder="Điện thoại"
            value={newPartner.phone}
            onChange={(e) => setNewPartner((p) => ({ ...p, phone: e.target.value }))}
          />
          <Input
            placeholder="Email"
            value={newPartner.email}
            onChange={(e) => setNewPartner((p) => ({ ...p, email: e.target.value }))}
          />
          <Select
            placeholder="Nhóm đối tác"
            options={DELIVERY_PARTNER_GROUPS.filter((g) => g.value !== 'all')}
            value={newPartner.group}
            onChange={(v) => setNewPartner((p) => ({ ...p, group: v }))}
            allowClear
          />
          <Radio.Group
            value={newPartner.type}
            onChange={(e) => setNewPartner((p) => ({ ...p, type: e.target.value }))}
            style={{ display: 'flex', gap: 12 }}
          >
            <Radio value="individual">Cá nhân</Radio>
            <Radio value="company">Công ty</Radio>
          </Radio.Group>
          <Input.TextArea
            placeholder="Ghi chú"
            autoSize={{ minRows: 3, maxRows: 4 }}
            value={newPartner.note}
            onChange={(e) => setNewPartner((p) => ({ ...p, note: e.target.value }))}
          />
        </div>
      </Modal>

      <input
        type="file"
        ref={fileInputRef}
        accept=".csv,.xlsx"
        style={{ display: 'none' }}
        onChange={handleImport}
      />
    </div>
  );
};

export default DeliveryPartnerPage;
