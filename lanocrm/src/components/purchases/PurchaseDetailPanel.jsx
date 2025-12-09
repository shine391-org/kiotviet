// src/components/purchases/PurchaseDetailPanel.jsx
import React, { useState } from 'react';
import { Button, Space, Table, Tabs, Tag, Typography } from 'antd';
import {
    CopyOutlined,
    DeleteOutlined,
    EditOutlined,
    ExportOutlined,
    MailOutlined,
    PrinterOutlined,
    RollbackOutlined,
    SaveOutlined,
} from '@ant-design/icons';
import dayjs from 'dayjs';
import { PURCHASE_STATUSES, PAYMENT_STATUSES } from '../../constants/purchases';

const { TabPane } = Tabs;

const currency = (value) => {
    if (value === null || value === undefined) return '0';
    return new Intl.NumberFormat('vi-VN').format(value);
};

/**
 * PurchaseDetailPanel - Expandable row detail panel for purchase orders
 * @agent-layer: frontend-component
 * @agent-pattern: detail-panel with tabs (like SupplierDetailPanel)
 */
const PurchaseDetailPanel = ({ purchase, loading, onReceiptCodeClick }) => {
    const [activeTab, setActiveTab] = useState('info');

    if (!purchase) return null;

    const statusInfo = PURCHASE_STATUSES.find((s) => s.value === purchase.status) || {};
    const paymentInfo = PAYMENT_STATUSES.find((s) => s.value === purchase.payment_status) || {};

    const items = purchase.items || [];
    const totalQty = items.reduce((sum, i) => sum + (i.quantity || 0), 0);
    const totalAmount = items.reduce((sum, i) => sum + (i.amount || 0), 0);

    const itemColumns = [
        {
            title: 'Mã hàng',
            dataIndex: 'product_code',
            width: 100,
            render: (text) => <Typography.Link>{text || '—'}</Typography.Link>,
        },
        { title: 'Tên hàng', dataIndex: 'product_name', ellipsis: true },
        { title: 'Số lượng', dataIndex: 'quantity', align: 'center', width: 80, render: (v) => v || 0 },
        { title: 'Đơn giá', dataIndex: 'rate', align: 'right', width: 100, render: (v) => currency(v) },
        { title: 'Giảm giá', dataIndex: 'discount', align: 'right', width: 80, render: (v) => currency(v || 0) },
        { title: 'Giá nhập', dataIndex: 'import_price', align: 'right', width: 100, render: (v) => currency(v) },
        {
            title: 'Thành tiền',
            dataIndex: 'amount',
            align: 'right',
            width: 120,
            render: (v) => <strong style={{ color: '#1890ff' }}>{currency(v)}</strong>,
        },
    ];

    // Payment history for tab 2
    const paymentHistory = purchase.payment_history || [];
    const paymentColumns = [
        {
            title: 'Mã phiếu',
            dataIndex: 'receipt_code',
            width: 120,
            render: (code) => (
                <Typography.Link onClick={() => onReceiptCodeClick?.(code)}>{code}</Typography.Link>
            ),
        },
        {
            title: 'Thời gian',
            dataIndex: 'created_at',
            width: 140,
            render: (v) => (v ? dayjs(v).format('DD/MM/YYYY HH:mm') : '—'),
        },
        { title: 'Người tạo', dataIndex: 'creator_name', width: 120 },
        { title: 'Phương thức', dataIndex: 'payment_method', width: 120 },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            width: 100,
            render: (v) => <Tag color={v === 'cancelled' ? 'red' : 'green'}>{v === 'cancelled' ? 'Đã hủy' : 'Hoàn thành'}</Tag>,
        },
        {
            title: 'Tiền chi',
            dataIndex: 'amount',
            align: 'right',
            width: 100,
            render: (v) => currency(v),
        },
    ];

    return (
        <div style={{ padding: '12px 16px', background: '#fafafa' }}>
            <Tabs activeKey={activeTab} onChange={setActiveTab} size="small">
                {/* Tab 1: Thông tin */}
                <TabPane tab="Thông tin" key="info">
                    {/* Header */}
                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 12 }}>
                        <Space>
                            <strong style={{ fontSize: 16 }}>{purchase.order_number || purchase.po_number}</strong>
                            <Tag color={statusInfo.color}>{statusInfo.label}</Tag>
                            {paymentInfo.value && <Tag color={paymentInfo.color}>{paymentInfo.label}</Tag>}
                        </Space>
                        <Tag>{purchase.branch_name || 'Chi nhánh'}</Tag>
                    </div>

                    {/* Meta */}
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 16, marginBottom: 16 }}>
                        <div>
                            <span style={{ color: '#8c8c8c' }}>Người tạo: </span>
                            {purchase.creator_name || '—'}
                        </div>
                        <div>
                            <span style={{ color: '#8c8c8c' }}>Tên NCC: </span>
                            <Typography.Link>{purchase.supplier_name || '—'}</Typography.Link>
                        </div>
                        <div>
                            <span style={{ color: '#8c8c8c' }}>Người nhập: </span>
                            {purchase.receiver_name || purchase.creator_name || '—'}
                        </div>
                        <div>
                            <span style={{ color: '#8c8c8c' }}>Ngày nhập: </span>
                            {purchase.order_date ? dayjs(purchase.order_date).format('DD/MM/YYYY HH:mm') : '—'}
                        </div>
                    </div>

                    {/* Items table */}
                    <Table
                        columns={itemColumns}
                        dataSource={items}
                        rowKey="id"
                        pagination={false}
                        size="small"
                        loading={loading}
                        style={{ marginBottom: 16 }}
                    />

                    {/* Notes */}
                    <div style={{ marginBottom: 16, color: '#8c8c8c' }}>
                        <EditOutlined style={{ marginRight: 8 }} />
                        {purchase.notes || 'Ghi chú...'}
                    </div>

                    {/* Summary */}
                    <div style={{ display: 'flex', justifyContent: 'space-between', padding: '12px 16px', background: '#f5f5f5', borderRadius: 6, marginBottom: 16 }}>
                        <div>
                            <div>Số lượng mặt hàng: <strong>{items.length}</strong></div>
                            <div>Tổng tiền hàng ({totalQty}): <strong>{currency(totalAmount)}</strong></div>
                        </div>
                        <div style={{ textAlign: 'right' }}>
                            <div>Giảm giá: {currency(purchase.discount || 0)}</div>
                            <div style={{ fontSize: 18, fontWeight: 600, color: '#1890ff' }}>
                                Tổng cộng: {currency(purchase.total || totalAmount)}
                            </div>
                            <div>Tiền đã trả NCC: {currency(purchase.paid_amount || 0)}</div>
                        </div>
                    </div>

                    {/* Actions */}
                    <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                        <Button icon={<DeleteOutlined />} danger>Hủy</Button>
                        <Space>
                            <Button icon={<CopyOutlined />}>Sao chép</Button>
                            <Button icon={<ExportOutlined />}>Xuất file</Button>
                            <Button icon={<MailOutlined />}>Gửi Email</Button>
                            <Button icon={<SaveOutlined />}>Lưu</Button>
                            <Button icon={<RollbackOutlined />}>Trả hàng nhập</Button>
                            <Button icon={<PrinterOutlined />}>In tem mã</Button>
                        </Space>
                    </div>
                </TabPane>

                {/* Tab 2: Lịch sử thanh toán */}
                <TabPane tab="Lịch sử thanh toán" key="payment_history">
                    <Table
                        columns={paymentColumns}
                        dataSource={paymentHistory}
                        rowKey="id"
                        pagination={false}
                        size="small"
                        locale={{ emptyText: 'Không có dữ liệu' }}
                    />
                </TabPane>
            </Tabs>
        </div>
    );
};

export default PurchaseDetailPanel;
