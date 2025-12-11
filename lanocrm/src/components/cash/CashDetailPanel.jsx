import React from 'react';
import { Tabs, Table, Tag, Space, Typography, Button, Divider } from 'antd';
import {
    EditOutlined,
    PrinterOutlined,
    DeleteOutlined,
    UpOutlined
} from '@ant-design/icons';
import { Link } from 'react-router-dom';
import dayjs from 'dayjs';
import { formatCurrency } from '../../utils/formatters';
import styles from '../../pages/cash/CashBookPage.module.css';

const { Text } = Typography;

/**
 * CashDetailPanel - Expandable detail panel for cash transactions
 * Based on KiotViet design pattern
 */
const CashDetailPanel = ({
    record,
    branchesMap = {},
    onEdit,
    onDelete,
    onPrint
}) => {
    if (!record) return null;

    const isReceipt = record.type === 'RECEIPT';
    const typeLabel = isReceipt ? 'Phiếu thu' : 'Phiếu chi';
    const typeCode = record.reference_code || `TTH${String(record.id).padStart(6, '0')}`;

    // Status mapping  
    const statusConfig = {
        approved: { label: 'Đã thanh toán', color: 'green' },
        pending: { label: 'Chờ xử lý', color: 'orange' },
        cancelled: { label: 'Đã hủy', color: 'red' },
    };
    const status = statusConfig[record.status] || { label: record.status || '—', color: 'default' };

    // Accounting status
    const accountingLabel = record.affects_accounting !== false ? 'Hạch toán' : 'Không hạch toán';
    const accountingColor = record.affects_accounting !== false ? 'green' : 'orange';

    // Branch name
    const branchName = branchesMap[record.branch_id] || `CN #${record.branch_id || '—'}`;

    // Related invoices (if any)
    const relatedInvoices = record.related_invoices || [];

    const invoiceColumns = [
        {
            title: 'Mã phiếu',
            dataIndex: 'invoice_code',
            width: 140,
            render: (code, r) => (
                <Link to={`/orders/invoices/${r.invoice_id || ''}`} className={styles.link}>
                    {code || '—'}
                </Link>
            )
        },
        {
            title: 'Thời gian',
            dataIndex: 'created_at',
            width: 160,
            render: (v) => v ? dayjs(v).format('DD/MM/YYYY HH:mm') : '—'
        },
        {
            title: 'Giá trị phiếu',
            dataIndex: 'total_amount',
            align: 'right',
            render: (v) => formatCurrency(v || 0),
            width: 120
        },
        {
            title: 'Đã thu trước',
            dataIndex: 'paid_before',
            align: 'right',
            render: (v) => formatCurrency(v || 0),
            width: 120
        },
        {
            title: 'Giá trị thu',
            dataIndex: 'paid_amount',
            align: 'right',
            render: (v) => formatCurrency(v || 0),
            width: 120
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            width: 120,
            render: (v) => (
                <span style={{ color: v === 'completed' ? '#52c41a' : '#1890ff' }}>
                    {v === 'completed' ? 'Đã thanh toán' : 'Đang xử lý'}
                </span>
            )
        },
    ];

    // Build payer info as clickable if has customer_id
    const renderPayerInfo = () => {
        const parts = [];

        // Customer code - clickable
        if (record.customer_code || record.payer_code) {
            parts.push(
                <Link
                    key="code"
                    to={`/customers?code=${record.customer_code || record.payer_code}`}
                    className={styles.link}
                    onClick={(e) => e.stopPropagation()}
                >
                    {record.customer_code || record.payer_code}
                </Link>
            );
        }

        // Payer name
        if (record.payer_name) {
            if (record.customer_id) {
                parts.push(
                    <Link
                        key="name"
                        to={`/customers?id=${record.customer_id}`}
                        className={styles.link}
                        onClick={(e) => e.stopPropagation()}
                    >
                        {record.payer_name}
                    </Link>
                );
            } else {
                parts.push(<span key="name">{record.payer_name}</span>);
            }
        }

        // Phone
        if (record.payer_phone) {
            parts.push(<span key="phone" style={{ color: '#1890ff' }}>{record.payer_phone}</span>);
        }

        // Address
        if (record.payer_address) {
            parts.push(<span key="addr">{record.payer_address}</span>);
        }

        return parts.length > 0 ? parts.reduce((prev, curr, i) =>
            prev === null ? [curr] : [...prev, <span key={`sep-${i}`}>, </span>, curr]
            , null) : '—';
    };

    return (
        <div className={styles.detailPanel} onClick={(e) => e.stopPropagation()}>
            {/* Header section */}
            <div className={styles.detailHeader}>
                <div className={styles.detailHeaderLeft}>
                    <Tabs
                        activeKey="info"
                        size="small"
                        items={[{ key: 'info', label: 'Thông tin' }]}
                        className={styles.detailTabs}
                    />
                </div>
            </div>

            {/* Main Info Section */}
            <div className={styles.detailContent}>
                {/* Title row with status */}
                <div className={styles.detailTitleRow}>
                    <Space size="middle">
                        <Text strong style={{ fontSize: 16 }}>
                            {typeLabel} <Text type="secondary">{typeCode}</Text>
                        </Text>
                        <Tag color={status.color}>{status.label}</Tag>
                        <Tag color={accountingColor}>{accountingLabel}</Tag>
                    </Space>
                    <Space>
                        <span className={styles.branchLabel}>
                            📍 {branchName}
                        </span>
                    </Space>
                </div>

                {/* Info Grid - First row: Người tạo, Người thu, Thời gian */}
                <div className={styles.detailInfoRow}>
                    <div className={styles.detailInfoItem}>
                        <Text type="secondary">Người tạo:</Text>
                        <Text style={{ marginLeft: 8 }}>
                            {record.created_by_name || '—'}
                        </Text>
                    </div>
                    <div className={styles.detailInfoItem}>
                        <Text type="secondary">{isReceipt ? 'Người thu:' : 'Người chi:'}</Text>
                        <Text style={{ marginLeft: 8 }}>
                            {record.staff_name || record.created_by_name || '—'}
                        </Text>
                    </div>
                    <div className={styles.detailInfoItem}>
                        <Text type="secondary">Thời gian:</Text>
                        <Text style={{ marginLeft: 8 }}>
                            {record.transaction_date ? dayjs(record.transaction_date).format('DD/MM/YYYY HH:mm') : '—'}
                        </Text>
                    </div>
                </div>

                <Divider className={styles.detailDivider} />

                {/* Second row: Số tiền, Loại thu, Đối tượng nộp, Phương thức thanh toán */}
                <div className={styles.detailInfoGrid}>
                    <div className={styles.detailInfoItem}>
                        <Text type="secondary" className={styles.detailLabel}>Số tiền</Text>
                        <Text strong className={isReceipt ? styles.amountReceipt : styles.amountPayment}>
                            {formatCurrency(record.amount)}
                        </Text>
                    </div>
                    <div className={styles.detailInfoItem}>
                        <Text type="secondary" className={styles.detailLabel}>
                            {isReceipt ? 'Loại thu' : 'Loại chi'}
                        </Text>
                        <Text className={styles.detailValue}>
                            {record.category || 'Thu Tiền khách trả'}
                        </Text>
                    </div>
                    <div className={styles.detailInfoItem}>
                        <Text type="secondary" className={styles.detailLabel}>Đối tượng nộp</Text>
                        <Text className={styles.detailValue}>
                            {record.payer_type || 'Khách hàng'}
                        </Text>
                    </div>
                    <div className={styles.detailInfoItem}>
                        <Text type="secondary" className={styles.detailLabel}>Phương thức thanh toán</Text>
                        <Text className={styles.detailValue} style={{ color: '#1890ff' }}>
                            {record.payment_method === 'cash' ? 'Tiền mặt' :
                                record.payment_method === 'bank' ? 'Ngân hàng' :
                                    record.payment_method === 'bank_transfer' ? 'Chuyển khoản' :
                                        record.payment_method || 'Tiền mặt'}
                        </Text>
                    </div>
                </div>

                <Divider className={styles.detailDivider} />

                {/* Payer Info - with clickable links */}
                <div className={styles.detailPayerSection}>
                    <Text type="secondary" className={styles.detailLabel}>
                        {isReceipt ? 'Người nộp' : 'Người nhận'}
                    </Text>
                    <div className={styles.detailPayerInfo}>
                        {renderPayerInfo()}
                    </div>
                </div>

                {/* Related Invoices Table */}
                {relatedInvoices.length > 0 && (
                    <div className={styles.detailInvoiceSection}>
                        <div className={styles.detailInvoiceHeader}>
                            <Text type="secondary">
                                {typeLabel} tự động được gán với hóa đơn{' '}
                                <Link
                                    to={`/orders/invoices/${relatedInvoices[0]?.invoice_id || ''}`}
                                    onClick={(e) => e.stopPropagation()}
                                >
                                    {relatedInvoices[0]?.invoice_code || ''}
                                </Link>
                            </Text>
                            <UpOutlined style={{ cursor: 'pointer' }} />
                        </div>
                        <Table
                            dataSource={relatedInvoices}
                            columns={invoiceColumns}
                            size="small"
                            pagination={false}
                            rowKey={(r, index) => r.id || r.invoice_code || `inv-${index}`}
                            className={styles.detailInvoiceTable}
                        />
                    </div>
                )}

                {/* Notes */}
                <div className={styles.detailNotesSection}>
                    <EditOutlined className={styles.notesIcon} />
                    <Text type="secondary" italic>
                        {record.note || record.description || 'Chưa có ghi chú'}
                    </Text>
                </div>

                {/* Action Buttons - matching KiotViet layout: Hủy left, Chỉnh sửa + In right */}
                <div className={styles.detailActions}>
                    <Button
                        icon={<DeleteOutlined />}
                        onClick={(e) => {
                            e.stopPropagation();
                            onDelete?.(record);
                        }}
                    >
                        Hủy
                    </Button>
                    <Space>
                        <Button
                            type="primary"
                            icon={<EditOutlined />}
                            onClick={(e) => {
                                e.stopPropagation();
                                onEdit?.(record);
                            }}
                        >
                            Chỉnh sửa
                        </Button>
                        <Button
                            icon={<PrinterOutlined />}
                            onClick={(e) => {
                                e.stopPropagation();
                                onPrint?.(record);
                            }}
                        >
                            In
                        </Button>
                    </Space>
                </div>
            </div>
        </div>
    );
};

export default CashDetailPanel;
