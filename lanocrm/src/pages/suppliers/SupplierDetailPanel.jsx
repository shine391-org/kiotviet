// src/pages/suppliers/SupplierDetailPanel.jsx

import React, { useState } from 'react';
import {
    Tabs,
    Table,
    Button,
    Space,
    Tag,
    Select,
    Modal,
    message,
} from 'antd';
import {
    EditOutlined,
    DeleteOutlined,
    StopOutlined,
    ExportOutlined,
    DollarOutlined,
    PercentageOutlined,
    SwapOutlined,
} from '@ant-design/icons';
import dayjs from 'dayjs';
import styles from './SupplierDetailPanel.module.css';

const { TabPane } = Tabs;

/**
 * SupplierDetailPanel - Expandable detail panel for supplier
 * @agent-layer: frontend-component
 * @agent-pattern: detail-panel with tabs
 */

const currency = (value) => {
    if (value === null || value === undefined) return '0';
    return new Intl.NumberFormat('vi-VN').format(value);
};

const SupplierDetailPanel = ({
    supplier,
    onEdit,
    onDelete,
    onToggleStatus,
    onInvoiceInfoClick,
    onReceiptClick,
    onAdjust,
    onPayment,
    onDiscount,
    loading
}) => {
    const [activeTab, setActiveTab] = useState('info');
    const [payableFilter, setPayableFilter] = useState('all');

    if (!supplier) return null;

    const receipts = supplier.receipts || [];
    const payables = supplier.payables || [];

    // Filter payables by type
    const filteredPayables = payableFilter === 'all'
        ? payables
        : payables.filter((p) => p.type?.toLowerCase().includes(payableFilter.toLowerCase()));

    const handleToggleStatus = () => {
        const newStatus = supplier.status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        Modal.confirm({
            title: newStatus === 'INACTIVE' ? 'Ngừng hoạt động' : 'Kích hoạt lại',
            content: `Bạn có chắc muốn ${newStatus === 'INACTIVE' ? 'ngừng hoạt động' : 'kích hoạt lại'} nhà cung cấp này?`,
            okText: 'Đồng ý',
            cancelText: 'Hủy',
            onOk: () => {
                if (onToggleStatus) {
                    onToggleStatus(supplier.id, newStatus);
                } else {
                    message.success(`Đã ${newStatus === 'INACTIVE' ? 'ngừng hoạt động' : 'kích hoạt'} nhà cung cấp`);
                }
            },
        });
    };

    const handleDelete = () => {
        Modal.confirm({
            title: 'Xóa nhà cung cấp',
            content: 'Bạn có chắc muốn xóa nhà cung cấp này? Hành động này không thể hoàn tác.',
            okText: 'Xóa',
            okType: 'danger',
            cancelText: 'Hủy',
            onOk: () => {
                onDelete?.(supplier.id);
                message.success('Đã xóa nhà cung cấp');
            },
        });
    };

    // Receipt history table columns
    const receiptColumns = [
        {
            title: 'Mã phiếu',
            dataIndex: 'code',
            key: 'code',
            render: (text) => (
                <a onClick={() => onReceiptClick?.(text)} style={{ color: '#1890ff' }}>
                    {text}
                </a>
            ),
        },
        {
            title: 'Thời gian',
            dataIndex: 'time',
            key: 'time',
            render: (v) => v || '—',
        },
        {
            title: 'Người tạo',
            dataIndex: 'creator',
            key: 'creator',
        },
        {
            title: 'Chi nhánh',
            dataIndex: 'branch',
            key: 'branch',
        },
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
            render: (v) => <Tag color="success">{v}</Tag>,
        },
    ];

    // Payables table columns
    const payableColumns = [
        {
            title: 'Mã phiếu',
            dataIndex: 'code',
            key: 'code',
            render: (text) => (
                <a onClick={() => onReceiptClick?.(text)} style={{ color: '#1890ff' }}>
                    {text}
                </a>
            ),
        },
        {
            title: 'Thời gian',
            dataIndex: 'time',
            key: 'time',
        },
        {
            title: 'Loại',
            dataIndex: 'type',
            key: 'type',
        },
        {
            title: 'Giá trị',
            dataIndex: 'value',
            key: 'value',
            align: 'right',
            render: (v) => (
                <span style={{ color: v < 0 ? '#f5222d' : '#52c41a' }}>
                    {currency(v)}
                </span>
            ),
        },
        {
            title: 'Nợ cần trả nhà cung cấp',
            dataIndex: 'payable',
            key: 'payable',
            align: 'right',
            render: (v) => currency(v),
        },
    ];

    return (
        <div className={styles.panel}>
            <Tabs activeKey={activeTab} onChange={setActiveTab}>
                {/* Tab 1: Thông tin */}
                <TabPane tab="Thông tin" key="info">
                    <div className={styles.infoContent}>
                        <div className={styles.infoHeader}>
                            <div>
                                <h3 className={styles.supplierName}>
                                    {supplier.name} <span className={styles.supplierCode}>{supplier.code}</span>
                                </h3>
                                <div className={styles.infoMeta}>
                                    <span>Người tạo: {supplier.creator || '—'}</span>
                                    <span>Ngày tạo: {supplier.created_at ? dayjs(supplier.created_at).format('DD/MM/YYYY') : '—'}</span>
                                    <span>Nhóm nhà cung cấp: {supplier.group || 'Chưa có'}</span>
                                </div>
                            </div>
                            <div className={styles.branchTag}>Lano - HN</div>
                        </div>

                        <div className={styles.infoGrid}>
                            <div className={styles.infoItem}>
                                <label>Điện thoại</label>
                                <span>{supplier.phone || 'Chưa có'}</span>
                            </div>
                            <div className={styles.infoItem}>
                                <label>Email</label>
                                <span>{supplier.email || 'Chưa có'}</span>
                            </div>
                        </div>

                        <div className={styles.infoItem} style={{ marginTop: 16 }}>
                            <label>Địa chỉ</label>
                            <span>{supplier.address || 'Chưa có'}</span>
                        </div>

                        <div className={styles.infoItem} style={{ marginTop: 16 }}>
                            <a
                                style={{ color: '#1890ff', cursor: 'pointer' }}
                                onClick={() => onInvoiceInfoClick?.(supplier)}
                            >
                                Thêm thông tin xuất hóa đơn
                            </a>
                        </div>

                        <div className={styles.infoItem} style={{ marginTop: 8 }}>
                            <EditOutlined style={{ marginRight: 4 }} />
                            <span style={{ color: '#8c8c8c' }}>{supplier.note || 'Chưa có ghi chú'}</span>
                        </div>

                        {/* Actions */}
                        <div className={styles.infoActions}>
                            <Button icon={<DeleteOutlined />} danger onClick={handleDelete}>
                                Xóa
                            </Button>
                            <Space>
                                <Button type="primary" icon={<EditOutlined />} onClick={() => onEdit?.(supplier)}>
                                    Chỉnh sửa
                                </Button>
                                <Button
                                    icon={<StopOutlined />}
                                    onClick={handleToggleStatus}
                                >
                                    {supplier.status === 'ACTIVE' ? 'Ngừng hoạt động' : 'Kích hoạt lại'}
                                </Button>
                            </Space>
                        </div>
                    </div>
                </TabPane>

                {/* Tab 2: Lịch sử nhập/trả hàng */}
                <TabPane tab="Lịch sử nhập/trả hàng" key="receipts">
                    <Table
                        columns={receiptColumns}
                        dataSource={receipts}
                        rowKey="code"
                        pagination={false}
                        size="small"
                        locale={{ emptyText: 'Không có dữ liệu' }}
                    />
                    <div style={{ marginTop: 12 }}>
                        <Button icon={<ExportOutlined />}>Xuất file</Button>
                    </div>
                </TabPane>

                {/* Tab 3: Nợ cần trả nhà cung cấp */}
                <TabPane tab="Nợ cần trả nhà cung cấp" key="payables">
                    <div style={{ marginBottom: 12, display: 'flex', justifyContent: 'flex-end' }}>
                        <Select
                            value={payableFilter}
                            onChange={setPayableFilter}
                            style={{ width: 180 }}
                            options={[
                                { label: 'Tất cả giao dịch', value: 'all' },
                                { label: 'Thanh toán', value: 'thanh toán' },
                                { label: 'Nhập hàng', value: 'nhập hàng' },
                            ]}
                        />
                    </div>
                    <Table
                        columns={payableColumns}
                        dataSource={filteredPayables}
                        rowKey="code"
                        pagination={false}
                        size="small"
                        locale={{ emptyText: 'Không có dữ liệu' }}
                    />
                    <div className={styles.payableActions}>
                        <Space>
                            <Button
                                icon={<ExportOutlined />}
                                onClick={() => message.info('Tính năng xuất file công nợ đang phát triển')}
                            >
                                Xuất file công nợ
                            </Button>
                            <Button
                                icon={<ExportOutlined />}
                                onClick={() => message.info('Tính năng xuất file đang phát triển')}
                            >
                                Xuất file
                            </Button>
                        </Space>
                        <Space>
                            <Button type="primary" icon={<SwapOutlined />} onClick={() => onAdjust?.(supplier)}>
                                Điều chỉnh
                            </Button>
                            <Button icon={<DollarOutlined />} onClick={() => onPayment?.(supplier)}>
                                Thanh toán
                            </Button>
                            <Button icon={<PercentageOutlined />} onClick={() => onDiscount?.(supplier)}>
                                Chiết khấu thanh toán
                            </Button>
                        </Space>
                    </div>
                </TabPane>
            </Tabs>
        </div>
    );
};

export default SupplierDetailPanel;
