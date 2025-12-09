// src/components/purchaseReturns/PurchaseReturnDetailPanel.jsx
import React from 'react';
import { Button, Descriptions, Space, Spin, Table, Tag, Typography } from 'antd';
import { CopyOutlined, DeleteOutlined, ExportOutlined, PrinterOutlined, SaveOutlined } from '@ant-design/icons';
import { PURCHASE_RETURN_STATUSES, formatPurchaseReturnDate } from '../../constants/purchaseReturns';

const { Text, Link } = Typography;

const currency = (value) => {
    if (value === null || value === undefined) return '0';
    return new Intl.NumberFormat('vi-VN').format(value);
};

/**
 * PurchaseReturnDetailPanel - Expandable detail panel for purchase return
 * @agent-layer: frontend-component
 * @agent-pattern: detail-panel
 */
const PurchaseReturnDetailPanel = ({ purchaseReturn, loading }) => {
    if (loading) {
        return (
            <div style={{ padding: 24, textAlign: 'center' }}>
                <Spin />
            </div>
        );
    }

    if (!purchaseReturn) {
        return <div style={{ padding: 24 }}>Không có dữ liệu</div>;
    }

    const statusInfo = PURCHASE_RETURN_STATUSES.find((s) => s.value === purchaseReturn.status) || {};

    const itemColumns = [
        {
            title: 'Mã hàng',
            dataIndex: 'product_code',
            width: 120,
            render: (text, record) => (
                <Link style={{ color: '#1890ff' }}>{text || record.product_code}</Link>
            ),
        },
        {
            title: 'Tên hàng',
            dataIndex: 'product_name',
            width: 250,
        },
        {
            title: 'Số lượng',
            dataIndex: 'quantity',
            width: 100,
            align: 'right',
        },
        {
            title: 'Giá nhập',
            dataIndex: 'import_price',
            width: 120,
            align: 'right',
            render: (v) => currency(v),
        },
        {
            title: 'Giá trả lại',
            dataIndex: 'return_price',
            width: 120,
            align: 'right',
            render: (v) => currency(v),
        },
        {
            title: 'Giảm giá trả lại',
            dataIndex: 'discount_per_item',
            width: 130,
            align: 'right',
            render: (v) => currency(v || 0),
        },
        {
            title: 'Thành tiền',
            dataIndex: 'amount',
            width: 130,
            align: 'right',
            render: (v) => <span style={{ color: '#1890ff' }}>{currency(v)}</span>,
        },
    ];

    return (
        <div style={{ padding: '16px 24px', background: '#fafafa' }}>
            {/* Header section */}
            <div style={{ marginBottom: 16 }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <Text strong style={{ fontSize: 14 }}>Thông tin</Text>
                    <Text type="secondary">{purchaseReturn.branch_name || ''}</Text>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginTop: 8 }}>
                    <Text strong style={{ fontSize: 16, color: '#1890ff' }}>
                        {purchaseReturn.return_number}
                    </Text>
                    <Tag color={statusInfo.color}>{statusInfo.label || purchaseReturn.status}</Tag>
                </div>
            </div>

            {/* Info grid */}
            <Descriptions column={4} size="small" style={{ marginBottom: 16 }}>
                <Descriptions.Item label="Người tạo">
                    {purchaseReturn.creator_name || '—'}
                </Descriptions.Item>
                <Descriptions.Item label="Người trả">
                    {purchaseReturn.returner_name || '—'}
                </Descriptions.Item>
                <Descriptions.Item label="Ngày trả">
                    {formatPurchaseReturnDate(purchaseReturn.return_date)}
                </Descriptions.Item>
                <Descriptions.Item label="Tên NCC">
                    <Link>{purchaseReturn.supplier_name || '—'}</Link>
                </Descriptions.Item>
                <Descriptions.Item label="Mã nhập hàng">
                    <Link>{purchaseReturn.purchase_order_number || '—'}</Link>
                </Descriptions.Item>
            </Descriptions>

            {/* Items table */}
            <Table
                size="small"
                rowKey="id"
                dataSource={purchaseReturn.items || []}
                columns={itemColumns}
                pagination={false}
                bordered
                style={{ marginBottom: 16 }}
            />

            {/* Summary */}
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                {/* Notes */}
                <div style={{ flex: 1, marginRight: 24 }}>
                    <Text type="secondary">Ghi chú...</Text>
                    <div style={{ marginTop: 8, color: '#666' }}>
                        {purchaseReturn.notes || 'Không có ghi chú'}
                    </div>
                </div>

                {/* Totals */}
                <div style={{ width: 300 }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 4 }}>
                        <Text type="secondary">Số lượng mặt hàng</Text>
                        <Text>{purchaseReturn.items?.length || 0}</Text>
                    </div>
                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 4 }}>
                        <Text type="secondary">Tổng tiền hàng ({purchaseReturn.total_quantity || 0})</Text>
                        <Text>{currency(purchaseReturn.total_amount)}</Text>
                    </div>
                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 4 }}>
                        <Text type="secondary">Giảm giá(%)</Text>
                        <Text>{currency(purchaseReturn.discount)}</Text>
                    </div>
                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 4 }}>
                        <Text type="secondary">NCC cần trả</Text>
                        <Text>{currency(purchaseReturn.ncc_can_tra)}</Text>
                    </div>
                    <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                        <Text type="secondary">NCC đã trả</Text>
                        <Text>{currency(purchaseReturn.ncc_da_tra)}</Text>
                    </div>
                </div>
            </div>

            {/* Action buttons */}
            <div style={{ marginTop: 16, borderTop: '1px solid #e8e8e8', paddingTop: 16, display: 'flex', justifyContent: 'space-between' }}>
                <Space>
                    <Button icon={<DeleteOutlined />}>Hủy</Button>
                    <Button icon={<CopyOutlined />}>Sao chép</Button>
                    <Button icon={<ExportOutlined />}>Xuất file</Button>
                </Space>
                <Space>
                    <Button type="primary" icon={<SaveOutlined />}>Lưu</Button>
                    <Button icon={<PrinterOutlined />}>In</Button>
                </Space>
            </div>
        </div>
    );
};

export default PurchaseReturnDetailPanel;
