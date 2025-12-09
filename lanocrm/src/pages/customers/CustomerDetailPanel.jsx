// src/pages/customers/CustomerDetailPanel.jsx

import React, { useState } from 'react';
import {
    Tabs,
    Table,
    Button,
    Space,
    Tag,
    Select,
    Modal,
    Empty,
    App,
    Avatar,
} from 'antd';
import {
    EditOutlined,
    DeleteOutlined,
    StopOutlined,
    ExportOutlined,
    DollarOutlined,
    PercentageOutlined,
    SwapOutlined,
    PlusOutlined,
    QrcodeOutlined,
    UserOutlined,
} from '@ant-design/icons';
import dayjs from 'dayjs';
import styles from './CustomerDetailPanel.module.css';

/**
 * CustomerDetailPanel - Expandable detail panel for customer
 * @agent-layer: frontend-component
 * @agent-pattern: detail-panel with tabs
 */

const currency = (value) => {
    if (value === null || value === undefined) return '0';
    return new Intl.NumberFormat('vi-VN').format(value);
};

const CustomerDetailPanel = ({
    customer,
    onEdit,
    onDelete,
    onToggleStatus,
    onInvoiceInfoClick,
    onAddAddress,
    onOrderClick,
    onReceiptClick,
    onPayment,
    onAdjust,
    onDiscount,
    onCreateQR,
    loading,
}) => {
    const { message } = App.useApp();
    const [activeTab, setActiveTab] = useState('info');
    const [debtFilter, setDebtFilter] = useState('all');
    const [exportingOrders, setExportingOrders] = useState(false);
    const [exportingDebt, setExportingDebt] = useState(false);

    if (!customer) return null;

    const handleToggleStatus = () => {
        const newStatus = customer.status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        Modal.confirm({
            title: newStatus === 'INACTIVE' ? 'Ngừng hoạt động' : 'Kích hoạt lại',
            content: `Bạn có chắc muốn ${newStatus === 'INACTIVE' ? 'ngừng hoạt động' : 'kích hoạt lại'} khách hàng này?`,
            okText: 'Đồng ý',
            cancelText: 'Hủy',
            onOk: () => {
                if (onToggleStatus) {
                    onToggleStatus(customer.id, newStatus);
                }
            },
        });
    };

    const handleDelete = () => {
        Modal.confirm({
            title: 'Xóa khách hàng',
            content: 'Bạn có chắc muốn xóa khách hàng này? Hành động này không thể hoàn tác.',
            okText: 'Xóa',
            okType: 'danger',
            cancelText: 'Hủy',
            onOk: () => {
                onDelete?.(customer.id);
            },
        });
    };

    const handleExportOrders = async () => {
        setExportingOrders(true);
        try {
            // Feature not yet implemented - show info message
            message.info('Tính năng xuất file lịch sử bán hàng đang được phát triển');
        } finally {
            setExportingOrders(false);
        }
    };

    const handleExportDebt = async () => {
        setExportingDebt(true);
        try {
            // Feature not yet implemented - show info message
            message.info('Tính năng xuất file công nợ đang được phát triển');
        } finally {
            setExportingDebt(false);
        }
    };

    // Get data from customer object
    const addresses = customer.addresses || [];
    const orders = customer.orders || [];
    const debts = customer.debts || [];

    // Filter debts by type
    const filteredDebts = debtFilter === 'all'
        ? debts
        : debts.filter((d) => d.type?.toLowerCase().includes(debtFilter.toLowerCase()));

    // Address table columns
    const addressColumns = [
        { title: 'Tên địa chỉ', dataIndex: 'name', key: 'name' },
        { title: 'Tên người nhận', dataIndex: 'recipient_name', key: 'recipient_name' },
        { title: 'Số điện thoại', dataIndex: 'phone', key: 'phone' },
        { title: 'Địa chỉ nhận', dataIndex: 'address', key: 'address', ellipsis: true },
        {
            title: 'Ngày tạo',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (v) => v ? dayjs(v).format('DD/MM/YYYY') : '—',
        },
    ];

    // Order history table columns
    const orderColumns = [
        {
            title: 'Mã hóa đơn',
            dataIndex: 'code',
            key: 'code',
            render: (text) => (
                <a onClick={() => onOrderClick?.(text)} className={styles.link}>
                    {text}
                </a>
            ),
        },
        {
            title: 'Thời gian',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (v) => v ? dayjs(v).format('DD/MM/YYYY HH:mm') : '—',
        },
        { title: 'Người bán', dataIndex: 'seller', key: 'seller' },
        { title: 'Chi nhánh', dataIndex: 'branch', key: 'branch' },
        {
            title: 'Tổng cộng',
            dataIndex: 'total',
            key: 'total',
            align: 'right',
            render: (v) => currency(v),
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            key: 'status',
            render: (v) => {
                const colorMap = {
                    'Hoàn thành': 'success',
                    'COMPLETED': 'success',
                    'DELIVERED': 'success',
                    'Đang xử lý': 'processing',
                    'PENDING': 'warning',
                    'Đã hủy': 'error',
                    'CANCELLED': 'error',
                };
                return <Tag color={colorMap[v] || 'default'}>{v}</Tag>;
            },
        },
    ];

    // Debt table columns
    const debtColumns = [
        {
            title: 'Mã phiếu',
            dataIndex: 'code',
            key: 'code',
            render: (text) => (
                <a onClick={() => onReceiptClick?.(text)} className={styles.link}>
                    {text}
                </a>
            ),
        },
        {
            title: 'Thời gian',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (v) => v ? dayjs(v).format('DD/MM/YYYY HH:mm') : '—',
        },
        { title: 'Loại', dataIndex: 'type', key: 'type' },
        {
            title: 'Giá trị',
            dataIndex: 'value',
            key: 'value',
            align: 'right',
            render: (v) => (
                <span style={{ color: v < 0 ? '#f5222d' : 'inherit' }}>
                    {currency(v)}
                </span>
            ),
        },
        {
            title: 'Dư nợ khách hàng',
            dataIndex: 'balance',
            key: 'balance',
            align: 'right',
            render: (v) => currency(v),
        },
    ];

    // Get customer initials for avatar
    const getInitials = (name) => {
        if (!name) return '?';
        return name
            .split(' ')
            .map((p) => p[0])
            .join('')
            .slice(0, 2)
            .toUpperCase();
    };

    const tabItems = [
        {
            key: 'info',
            label: 'Thông tin',
            children: (
                <div className={styles.infoContent}>
                    <div className={styles.infoHeader}>
                        <div className={styles.infoHeaderLeft}>
                            <Avatar
                                size={56}
                                icon={<UserOutlined />}
                                className={styles.avatar}
                            >
                                {getInitials(customer.name)}
                            </Avatar>
                            <div>
                                <h3 className={styles.customerName}>
                                    {customer.name} <span className={styles.customerCode}>{customer.code || `KH${customer.id}`}</span>
                                </h3>
                                <div className={styles.infoMeta}>
                                    <span>Người tạo: <strong>{customer.created_by || '—'}</strong></span>
                                    <span>Ngày tạo: {customer.created_at ? dayjs(customer.created_at).format('DD/MM/YYYY') : '—'}</span>
                                    <span>
                                        Nhóm khách:{' '}
                                        <a className={styles.link} onClick={() => onEdit?.(customer)}>
                                            {customer.customer_group_name || 'Chưa có'}
                                        </a>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div className={styles.branchTag}>Lano - HN</div>
                    </div>

                    <div className={styles.infoGrid}>
                        <div className={styles.infoItem}>
                            <label>Điện thoại</label>
                            <span>{customer.phone || 'Chưa có'}</span>
                        </div>
                        <div className={styles.infoItem}>
                            <label>Sinh nhật</label>
                            <span>{customer.birthday ? dayjs(customer.birthday).format('DD/MM/YYYY') : 'Chưa có'}</span>
                        </div>
                        <div className={styles.infoItem}>
                            <label>Giới tính</label>
                            <span>
                                {customer.gender === 'MALE' ? 'Nam' : customer.gender === 'FEMALE' ? 'Nữ' : 'Chưa có'}
                            </span>
                        </div>
                    </div>

                    <div className={styles.infoGrid} style={{ marginTop: 16 }}>
                        <div className={styles.infoItem}>
                            <label>Email</label>
                            <span>{customer.email || 'Chưa có'}</span>
                        </div>
                        <div className={styles.infoItem}>
                            <label>Facebook</label>
                            <span>{customer.facebook || 'Chưa có'}</span>
                        </div>
                    </div>

                    <div className={styles.infoItem} style={{ marginTop: 16 }}>
                        <label>Địa chỉ</label>
                        <span>{customer.address || 'Chưa có'}</span>
                    </div>

                    <div className={styles.infoItem} style={{ marginTop: 16 }}>
                        <a className={styles.link} onClick={() => onInvoiceInfoClick?.(customer)}>
                            Thêm thông tin xuất hóa đơn
                        </a>
                    </div>

                    <div className={styles.infoItem} style={{ marginTop: 8 }}>
                        <span style={{ color: '#8c8c8c' }}>
                            <EditOutlined style={{ marginRight: 4 }} />
                            {customer.notes || 'Chưa có ghi chú'}
                        </span>
                    </div>

                    {/* Actions */}
                    <div className={styles.infoActions}>
                        <Button icon={<DeleteOutlined />} danger onClick={handleDelete}>
                            Xóa
                        </Button>
                        <Space>
                            <Button type="primary" icon={<EditOutlined />} onClick={() => onEdit?.(customer)}>
                                Chỉnh sửa
                            </Button>
                            <Button icon={<StopOutlined />} onClick={handleToggleStatus}>
                                {customer.status === 'ACTIVE' ? 'Ngừng hoạt động' : 'Kích hoạt lại'}
                            </Button>
                        </Space>
                    </div>
                </div>
            ),
        },
        {
            key: 'addresses',
            label: 'Địa chỉ nhận hàng',
            children: (
                <div>
                    <Table
                        columns={addressColumns}
                        dataSource={addresses}
                        rowKey="id"
                        pagination={false}
                        size="small"
                        locale={{
                            emptyText: (
                                <Empty
                                    image={Empty.PRESENTED_IMAGE_SIMPLE}
                                    description="Không tìm thấy kết quả nào phù hợp"
                                />
                            ),
                        }}
                    />
                    <div style={{ marginTop: 12, textAlign: 'right' }}>
                        <Button type="primary" icon={<PlusOutlined />} onClick={() => onAddAddress?.(customer)}>
                            Địa chỉ mới
                        </Button>
                    </div>
                </div>
            ),
        },
        {
            key: 'orders',
            label: 'Lịch sử bán/trả hàng',
            children: (
                <div>
                    <Table
                        columns={orderColumns}
                        dataSource={orders}
                        rowKey="code"
                        pagination={false}
                        size="small"
                        locale={{ emptyText: 'Không có dữ liệu' }}
                    />
                    <div style={{ marginTop: 12 }}>
                        <Button icon={<ExportOutlined />} onClick={handleExportOrders} loading={exportingOrders}>
                            Xuất file
                        </Button>
                    </div>
                </div>
            ),
        },
        {
            key: 'debts',
            label: 'Nợ cần thu từ khách',
            children: (
                <div>
                    <div style={{ marginBottom: 12, display: 'flex', justifyContent: 'flex-end' }}>
                        <Select
                            value={debtFilter}
                            onChange={setDebtFilter}
                            style={{ width: 180 }}
                            options={[
                                { label: 'Tất cả giao dịch', value: 'all' },
                                { label: 'Thanh toán', value: 'thanh toán' },
                                { label: 'Bán hàng', value: 'bán hàng' },
                            ]}
                        />
                    </div>
                    <Table
                        columns={debtColumns}
                        dataSource={filteredDebts}
                        rowKey="code"
                        pagination={false}
                        size="small"
                        locale={{ emptyText: 'Không có dữ liệu' }}
                    />
                    <div className={styles.debtActions}>
                        <Space>
                            <Button icon={<ExportOutlined />} onClick={handleExportDebt} loading={exportingDebt}>
                                Xuất file công nợ
                            </Button>
                            <Button icon={<ExportOutlined />}>
                                Xuất file
                            </Button>
                        </Space>
                        <Space>
                            <Button type="primary" icon={<DollarOutlined />} onClick={() => onPayment?.(customer)}>
                                Thanh toán
                            </Button>
                            <Button icon={<SwapOutlined />} onClick={() => onAdjust?.(customer)}>
                                Điều chỉnh
                            </Button>
                            <Button icon={<PercentageOutlined />} onClick={() => onDiscount?.(customer)}>
                                Chiết khấu thanh toán
                            </Button>
                            <Button icon={<QrcodeOutlined />} onClick={() => onCreateQR?.(customer)}>
                                Tạo QR
                            </Button>
                        </Space>
                    </div>
                </div>
            ),
        },
    ];

    return (
        <div className={styles.panel}>
            <Tabs activeKey={activeTab} onChange={setActiveTab} items={tabItems} />
        </div>
    );
};

export default CustomerDetailPanel;
