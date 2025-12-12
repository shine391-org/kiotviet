// src/pages/customers/vouchers/VoucherListPage.jsx

import React, { useEffect, useMemo, useState, useCallback } from 'react';
import {
    App,
    Button,
    Card,
    Checkbox,
    Dropdown,
    Input,
    Modal,
    Select,
    Space,
    Table,
    Tag,
    Tooltip,
} from 'antd';
import {
    PlusOutlined,
    ReloadOutlined,
    MenuOutlined,
    SearchOutlined,
    QuestionCircleOutlined,
    FullscreenOutlined,
    SettingOutlined,
} from '@ant-design/icons';
import dayjs from 'dayjs';
import couponApi from '../../../api/couponApi';
import CreateVoucherModal from './CreateVoucherModal';
import styles from './VoucherListPage.module.css';

const STATUS_OPTIONS = [
    { label: 'Tất cả', value: null },
    { label: 'Đang kích hoạt', value: 'active' },
    { label: 'Chưa kích hoạt', value: 'inactive' },
];

const currency = (value) => {
    if (value === null || value === undefined) return '0';
    return new Intl.NumberFormat('vi-VN').format(value);
};

const VoucherListPage = () => {
    const { message } = App.useApp();

    const [loading, setLoading] = useState(false);
    const [vouchers, setVouchers] = useState([]);
    const [pagination, setPagination] = useState({ page: 1, limit: 15, total: 0 });
    const [filters, setFilters] = useState({
        page: 1,
        limit: 15,
        search: '',
        status: null,
        branch_id: null,
    });
    const [searchText, setSearchText] = useState('');
    const [selectedId, setSelectedId] = useState(null);
    const [modalOpen, setModalOpen] = useState(false);
    const [editingVoucher, setEditingVoucher] = useState(null);
    const [visibleCols, setVisibleCols] = useState([
        'code',
        'name',
        'start_date',
        'expiry_date',
        'usage_limit',
        'discount_value',
        'status',
    ]);

    // Fetch vouchers from API
    const fetchVouchers = useCallback(async () => {
        setLoading(true);
        try {
            const response = await couponApi.getAll(filters);
            const data = response.data?.data || response.data || [];
            setVouchers(Array.isArray(data) ? data : []);
            setPagination({
                page: response.data?.page || filters.page,
                limit: response.data?.limit || filters.limit,
                total: response.data?.total || data.length,
            });
        } catch (err) {
            message.error('Không thể tải danh sách voucher');
            setVouchers([]);
        } finally {
            setLoading(false);
        }
    }, [filters, message]);

    useEffect(() => {
        fetchVouchers();
    }, [fetchVouchers]);

    // Debounce search
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
            {
                key: 'code',
                title: 'Mã đợt phát hành',
                dataIndex: 'code',
                width: 140,
                render: (v) => <span style={{ color: '#1890ff', fontWeight: 500 }}>{v}</span>,
            },
            {
                key: 'name',
                title: 'Tên đợt phát hành',
                dataIndex: 'name',
                width: 260,
                ellipsis: true,
            },
            {
                key: 'start_date',
                title: 'Từ ngày',
                dataIndex: 'start_date',
                width: 110,
                render: (v) => (v ? dayjs(v).format('DD/MM/YYYY') : '—'),
            },
            {
                key: 'expiry_date',
                title: 'Đến ngày',
                dataIndex: 'expiry_date',
                width: 110,
                render: (v) => (v ? dayjs(v).format('DD/MM/YYYY') : '—'),
            },
            {
                key: 'usage_limit',
                title: 'Số lượng',
                dataIndex: 'usage_limit',
                width: 100,
                align: 'right',
                render: (v) => v || '—',
            },
            {
                key: 'discount_value',
                title: 'Mệnh giá',
                dataIndex: 'discount_value',
                width: 120,
                align: 'right',
                render: (v) => currency(v),
            },
            {
                key: 'status',
                title: 'Trạng thái',
                dataIndex: 'status',
                width: 120,
                render: (v) => (
                    <Tag color={v === 'active' ? 'cyan' : 'default'}>
                        {v === 'active' ? 'Đang kích hoạt' : 'Chưa kích hoạt'}
                    </Tag>
                ),
            },
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

    const handleRefresh = () => {
        fetchVouchers();
    };

    const openCreate = () => {
        setEditingVoucher(null);
        setModalOpen(true);
    };

    const handleModalSuccess = () => {
        setModalOpen(false);
        setEditingVoucher(null);
        handleRefresh();
        message.success('Đã lưu voucher thành công');
    };

    const handleStatusChange = (status) => {
        setFilters((prev) => ({ ...prev, status, page: 1 }));
    };

    const handleDelete = (id) => {
        Modal.confirm({
            title: 'Xác nhận xóa',
            content: 'Bạn có chắc chắn muốn xóa voucher này không?',
            okText: 'Xóa',
            okType: 'danger',
            cancelText: 'Hủy',
            onOk: async () => {
                try {
                    await couponApi.delete(id);
                    message.success('Đã xóa voucher');
                    handleRefresh();
                } catch (err) {
                    message.error('Không thể xóa voucher');
                }
            },
        });
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

    // Detail Panel for expanded row
    const renderDetailPanel = (record) => (
        <div className={styles.detailPanel}>
            <div className={styles.detailHeader}>
                <div>
                    <h3 className={styles.detailTitle}>
                        {record.name}{' '}
                        <span style={{ color: '#1890ff', fontSize: 14 }}>{record.code}</span>
                    </h3>
                    <Tag color={record.status === 'active' ? 'cyan' : 'default'} className={styles.detailStatus}>
                        {record.status === 'active' ? 'Đang kích hoạt' : 'Chưa kích hoạt'}
                    </Tag>
                </div>
            </div>

            <div className={styles.detailStats}>
                <div className={styles.detailStatItem}>
                    <span className={styles.detailStatLabel}>Số lượng voucher</span>
                    <span className={styles.detailStatValue}>{record.usage_limit || 0}</span>
                </div>
                <div className={styles.detailStatItem}>
                    <span className={styles.detailStatLabel}>Đã phát hành</span>
                    <span className={styles.detailStatValue}>{record.used_count || 0}</span>
                </div>
                <div className={styles.detailStatItem}>
                    <span className={styles.detailStatLabel}>Đã sử dụng</span>
                    <span className={styles.detailStatValue}>0</span>
                </div>
                <div className={styles.detailStatItem}>
                    <span className={styles.detailStatLabel}>Giá trị sử dụng</span>
                    <span className={styles.detailStatValue}>0</span>
                </div>
            </div>

            <div className={styles.detailSection}>
                <div className={styles.detailRow}>
                    <div className={styles.detailItem}>
                        <span className={styles.detailItemLabel}>Hiệu lực</span>
                        <span className={styles.detailItemValue}>
                            {record.start_date ? dayjs(record.start_date).format('DD/MM/YYYY') : '—'} -{' '}
                            {record.expiry_date ? dayjs(record.expiry_date).format('DD/MM/YYYY') : '—'}
                        </span>
                    </div>
                    <div className={styles.detailItem}>
                        <span className={styles.detailItemLabel}>Mệnh giá</span>
                        <span className={styles.detailItemValue}>{currency(record.discount_value)}</span>
                    </div>
                    <div className={styles.detailItem}>
                        <span className={styles.detailItemLabel}>Chi nhánh</span>
                        <span className={styles.detailItemValue}>
                            {record.branch_id ? `Chi nhánh #${record.branch_id}` : 'Toàn hệ thống'}
                        </span>
                    </div>
                    <div className={styles.detailItem}>
                        <span className={styles.detailItemLabel}>Nhóm khách hàng</span>
                        <span className={styles.detailItemValue}>
                            {record.customer_group_id ? `Nhóm #${record.customer_group_id}` : 'Tất cả'}
                        </span>
                    </div>
                </div>
            </div>

            <div className={styles.detailSection}>
                <div className={styles.detailSectionTitle}>Người tạo giao dịch</div>
                <div className={styles.detailItemValue}>
                    {record.creator_id ? `Nhân viên #${record.creator_id}` : 'Tất cả'}
                </div>
            </div>

            <div className={styles.detailSection}>
                <div className={styles.detailSectionTitle}>Điều kiện mua hàng</div>
                <div className={styles.detailItemValue}>
                    Tổng tiền hàng tối thiểu từ {currency(record.min_amount || 0)}
                </div>
            </div>

            {record.description && (
                <div className={styles.detailSection}>
                    <div className={styles.detailItemValue} style={{ color: '#6b7280' }}>
                        📝 {record.description}
                    </div>
                </div>
            )}

            <div className={styles.detailActions}>
                <Button size="small" danger onClick={() => handleDelete(record.id)}>
                    🗑️ Xóa
                </Button>
                <Button
                    type="primary"
                    size="small"
                    style={{ marginLeft: 'auto' }}
                    onClick={() => {
                        setEditingVoucher(record);
                        setModalOpen(true);
                    }}
                >
                    ✏️ Chỉnh sửa
                </Button>
            </div>
        </div>
    );

    return (
        <div className={styles.page}>
            <div className={styles.content}>
                {/* Filter Sidebar */}
                <Card title="Voucher" className={styles.filterCard} size="small" bordered>
                    <div className={styles.filterGroup}>
                        <div className={styles.filterLabel}>Chi nhánh</div>
                        <Select
                            style={{ width: '100%' }}
                            placeholder="Chọn chi nhánh"
                            allowClear
                            options={[]}
                            onChange={(value) => setFilters((prev) => ({ ...prev, branch_id: value, page: 1 }))}
                        />
                    </div>

                    <div className={styles.filterGroup}>
                        <div className={styles.filterLabel}>Trạng thái</div>
                        <div className={styles.statusChips}>
                            {STATUS_OPTIONS.map((opt) => (
                                <Button
                                    key={opt.value || 'all'}
                                    type={filters.status === opt.value ? 'primary' : 'default'}
                                    onClick={() => handleStatusChange(opt.value)}
                                >
                                    {opt.label}
                                </Button>
                            ))}
                        </div>
                    </div>
                </Card>

                {/* Main Content */}
                <div className={styles.contentMain}>
                    <div className={styles.tablePanel}>
                        {/* Toolbar */}
                        <div className={styles.toolbarRow}>
                            <Input
                                className={styles.searchBox}
                                placeholder="Theo mã, tên đợt phát hành voucher"
                                prefix={<SearchOutlined />}
                                value={searchText}
                                onChange={(e) => setSearchText(e.target.value)}
                                allowClear
                                size="middle"
                            />
                            <Space className={styles.actions} wrap>
                                <Button type="primary" icon={<PlusOutlined />} onClick={openCreate}>
                                    Đợt phát hành voucher
                                </Button>
                                <Dropdown menu={columnMenu} trigger={['click']}>
                                    <Button icon={<MenuOutlined />} />
                                </Dropdown>
                                <Button icon={<FullscreenOutlined />} />
                                <Tooltip title="Cài đặt">
                                    <Button icon={<SettingOutlined />} />
                                </Tooltip>
                                <Tooltip title="Trợ giúp">
                                    <Button icon={<QuestionCircleOutlined />} />
                                </Tooltip>
                                <Tooltip title="Làm mới">
                                    <Button icon={<ReloadOutlined />} onClick={handleRefresh} />
                                </Tooltip>
                            </Space>
                        </div>

                        {/* Table */}
                        <div className={styles.tableWrapper}>
                            <Table
                                rowKey="id"
                                loading={loading}
                                bordered
                                dataSource={vouchers}
                                columns={columns}
                                pagination={{
                                    current: pagination.page || 1,
                                    pageSize: pagination.limit || 15,
                                    total: pagination.total || 0,
                                    showSizeChanger: true,
                                    pageSizeOptions: ['15', '20', '50', '100'],
                                    showTotal: (total, range) =>
                                        `Hiển thị ${range[0]}-${range[1]} trong ${total} đợt phát hành`,
                                    onChange: (page, pageSize) =>
                                        setFilters((prev) => ({ ...prev, page, limit: pageSize })),
                                }}
                                rowSelection={{
                                    type: 'checkbox',
                                    selectedRowKeys: selectedId ? [selectedId] : [],
                                    onChange: (keys) => {
                                        const id = keys[keys.length - 1];
                                        if (id && id !== selectedId) {
                                            setSelectedId(id);
                                        }
                                    },
                                }}
                                expandable={{
                                    expandedRowKeys: selectedId ? [selectedId] : [],
                                    expandIcon: () => null,
                                    expandedRowRender: renderDetailPanel,
                                }}
                                onRow={(record) => ({
                                    onClick: () => {
                                        if (record.id === selectedId) {
                                            setSelectedId(null);
                                        } else {
                                            setSelectedId(record.id);
                                        }
                                    },
                                    className: record.id === selectedId ? styles.selectedRow : '',
                                })}
                                size="middle"
                                scroll={{ x: 900 }}
                            />
                        </div>
                    </div>
                </div>
            </div>

            {/* Create/Edit Modal */}
            <CreateVoucherModal
                open={modalOpen}
                voucher={editingVoucher}
                onCancel={() => {
                    setModalOpen(false);
                    setEditingVoucher(null);
                }}
                onSuccess={handleModalSuccess}
            />
        </div>
    );
};

export default VoucherListPage;
