import React, { useState } from 'react';
import { Tabs, Table, Tag, Space, Typography, Button, Select, DatePicker, Input, Tooltip } from 'antd';
import { EditOutlined, StarOutlined, CopyOutlined, PrinterOutlined, ExportOutlined } from '@ant-design/icons';
import { Link } from 'react-router-dom';
import dayjs from 'dayjs';
import { RETURN_STATUSES, formatReturnDate } from '../../constants/returns';
import EditPaymentReceiptModal from './EditPaymentReceiptModal';
import styles from './ReturnDetailPanel.module.css';

const statusMap = RETURN_STATUSES.reduce((acc, s) => ({ ...acc, [s.value]: s }), {});

const ReturnDetailPanel = ({ data, onEdit, onDelete, onSave }) => {
    const [activeTab, setActiveTab] = useState('info');
    const [editReceiptOpen, setEditReceiptOpen] = useState(false);
    const [selectedReceipt, setSelectedReceipt] = useState(null);

    if (!data) return null;
    const statusMeta = statusMap[data.status] || {};

    // Item columns with clickable product code
    const itemColumns = [
        {
            title: 'Mã hàng',
            dataIndex: 'sku',
            width: 140,
            render: (sku, record) => (
                <Link to={`/products/${record.product_id || ''}`} className={styles.link}>
                    {sku || '—'}
                </Link>
            )
        },
        { title: 'Tên hàng', dataIndex: 'name', ellipsis: true },
        { title: 'Số lượng', dataIndex: 'quantity', align: 'center', width: 100 },
        {
            title: 'Giá trả hàng',
            dataIndex: 'return_price',
            align: 'right',
            render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
            width: 120
        },
        {
            title: 'Giảm giá',
            dataIndex: 'discount',
            align: 'right',
            render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
            width: 100
        },
        {
            title: 'Giá nhập lại',
            dataIndex: 'restock_price',
            align: 'right',
            render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
            width: 120
        },
        {
            title: 'Thành tiền',
            key: 'line_total',
            align: 'right',
            render: (_, r) => (Number(r.return_price || 0) * Number(r.quantity || 0) - Number(r.discount || 0)).toLocaleString('vi-VN'),
            width: 120,
        },
    ];

    // Payment history columns  
    const paymentColumns = [
        {
            title: 'Mã phiếu',
            dataIndex: 'receipt_code',
            width: 140,
            render: (code, record) => (
                <Typography.Link onClick={() => handleReceiptClick(record)}>
                    {code || '—'}
                </Typography.Link>
            )
        },
        {
            title: 'Thời gian',
            dataIndex: 'created_at',
            width: 160,
            render: (v) => formatReturnDate(v)
        },
        { title: 'Người tạo', dataIndex: 'creator_name', width: 140 },
        {
            title: 'Giá trị phiếu',
            dataIndex: 'amount',
            align: 'right',
            render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
            width: 120
        },
        { title: 'Phương thức', dataIndex: 'payment_method', width: 140 },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            width: 120,
            render: (v) => (
                <Tag color={v === 'completed' ? 'green' : 'blue'}>
                    {v === 'completed' ? 'Đã thanh toán' : 'Đang xử lý'}
                </Tag>
            )
        },
        {
            title: 'Tiền chi',
            dataIndex: 'paid_amount',
            align: 'right',
            render: (v) => (Number(v || 0)).toLocaleString('vi-VN'),
            width: 120
        },
    ];

    const handleReceiptClick = (receipt) => {
        setSelectedReceipt(receipt);
        setEditReceiptOpen(true);
    };

    // Calculate totals
    const itemCount = data.items?.length || 0;
    const goodsTotal = Number(data.goods_amount || 0);
    const discountTotal = Number(data.discount_amount || 0);
    const returnFee = Number(data.return_fee || 0);
    const needRefund = Number(data.need_refund || 0);
    const refundedAmount = Number(data.refunded_amount || 0);

    return (
        <div className={styles.panel}>
            {/* Header Row */}
            <div className={styles.headerRow}>
                <Space>
                    <StarOutlined className={styles.starIcon} />
                    <Typography.Text strong>{data.return_code}</Typography.Text>
                    <Typography.Text type="secondary">{data.seller_name || 'Chưa có'}</Typography.Text>
                    <Typography.Text type="secondary">{formatReturnDate(data.created_at)}</Typography.Text>
                    <Typography.Text strong>{data.customer_name || '—'}</Typography.Text>
                </Space>
                <Space>
                    <Typography.Text>{goodsTotal.toLocaleString('vi-VN')}</Typography.Text>
                    <Typography.Text>{discountTotal.toLocaleString('vi-VN')}</Typography.Text>
                    <Typography.Text>{needRefund.toLocaleString('vi-VN')}</Typography.Text>
                    <Tag color={statusMeta.color}>{statusMeta.label || data.status || '—'}</Tag>
                </Space>
            </div>

            {/* Tabs */}
            <Tabs
                activeKey={activeTab}
                onChange={setActiveTab}
                size="small"
                items={[
                    { key: 'info', label: 'Thông tin' },
                    { key: 'payments', label: 'Lịch sử thanh toán' },
                ]}
                className={styles.tabs}
            />

            {/* Tab Content */}
            {activeTab === 'info' && (
                <div className={styles.infoTab}>
                    {/* Customer Header */}
                    <div className={styles.customerHeader}>
                        <Typography.Title level={5} className={styles.customerName}>
                            {data.customer_name || '—'}
                            <EditOutlined className={styles.editIcon} />
                            <Typography.Text className={styles.returnCode}>{data.return_code}</Typography.Text>
                            <Tag color={statusMeta.color}>{statusMeta.label || data.status || '—'}</Tag>
                        </Typography.Title>
                        <Typography.Text type="secondary" className={styles.branchLabel}>
                            Lano - HN
                        </Typography.Text>
                    </div>

                    {/* Info Grid */}
                    <div className={styles.infoGrid}>
                        <div className={styles.infoCol}>
                            <div className={styles.infoRow}>
                                <span className={styles.label}>Người tạo:</span>
                                <Typography.Link>{data.seller_name || '—'}</Typography.Link>
                            </div>
                            <div className={styles.infoRow}>
                                <span className={styles.label}>Mã hóa đơn:</span>
                                <Link to={`/orders/invoices/${data.order_id || ''}`} className={styles.link}>
                                    {data.invoice_code || '—'}
                                </Link>
                                <span className={styles.subLabel}> - Mã vận đơn: </span>
                                <span>{data.shipping_code || '—'}</span>
                            </div>
                            <div className={styles.infoRow}>
                                <span className={styles.label}>Mã phiếu chi:</span>
                                <Typography.Link onClick={() => handleReceiptClick(data.payment_receipt)}>
                                    {data.payment_receipt?.receipt_code || '—'}
                                </Typography.Link>
                            </div>
                        </div>
                        <div className={styles.infoCol}>
                            <div className={styles.infoRow}>
                                <span className={styles.label}>Người nhận trả:</span>
                                <Select
                                    size="small"
                                    defaultValue={data.receiver_name || undefined}
                                    placeholder="Chọn người nhận"
                                    style={{ width: 160 }}
                                    options={[
                                        { label: data.receiver_name || 'Chưa có', value: data.receiver_name || '' },
                                    ]}
                                />
                            </div>
                            <div className={styles.infoRow}>
                                <span className={styles.label}>Kênh bán:</span>
                                <span>{data.channel || 'Bán trực tiếp'}</span>
                            </div>
                        </div>
                        <div className={styles.infoCol}>
                            <div className={styles.infoRow}>
                                <span className={styles.label}>Ngày trả:</span>
                                <DatePicker
                                    size="small"
                                    showTime
                                    format="DD/MM/YYYY HH:mm"
                                    defaultValue={data.return_time ? dayjs(data.return_time) : undefined}
                                    style={{ width: 200 }}
                                />
                            </div>
                            <div className={styles.infoRow}>
                                <span className={styles.label}>Bảng giá:</span>
                                <span>{data.price_list_name || 'Sales'}</span>
                            </div>
                        </div>
                    </div>

                    {/* Items Table */}
                    <div className={styles.itemsSection}>
                        <Table
                            dataSource={data.items || []}
                            columns={itemColumns}
                            size="small"
                            pagination={false}
                            rowKey={(r, index) => r.id || r.sku || `item-${index}`}
                            className={styles.itemsTable}
                        />
                    </div>

                    {/* Notes + Summary */}
                    <div className={styles.bottomSection}>
                        <div className={styles.notesArea}>
                            <Input.TextArea
                                placeholder="Ghi chú..."
                                defaultValue={data.notes}
                                rows={3}
                                className={styles.notesInput}
                            />
                        </div>
                        <div className={styles.summaryArea}>
                            <div className={styles.summaryRow}>
                                <span>Tổng tiền hàng trả ({itemCount})</span>
                                <span>{goodsTotal.toLocaleString('vi-VN')}</span>
                            </div>
                            <div className={styles.summaryRow}>
                                <span>Giảm giá phiếu trả</span>
                                <span>{discountTotal.toLocaleString('vi-VN')}</span>
                            </div>
                            <div className={styles.summaryRow}>
                                <span>Phí trả hàng</span>
                                <span>{returnFee.toLocaleString('vi-VN')}</span>
                            </div>
                            <div className={styles.summaryRow + ' ' + styles.highlight}>
                                <span>Cần trả khách</span>
                                <span>{needRefund.toLocaleString('vi-VN')}</span>
                            </div>
                            <div className={styles.summaryRow}>
                                <span>Đã trả khách</span>
                                <span>{refundedAmount.toLocaleString('vi-VN')}</span>
                            </div>
                        </div>
                    </div>

                    {/* Action Buttons */}
                    <div className={styles.actions}>
                        <Space>
                            <Button icon={<CopyOutlined />}>Hủy</Button>
                            <Button icon={<CopyOutlined />}>Sao chép</Button>
                            <Button icon={<ExportOutlined />}>Xuất file</Button>
                        </Space>
                        <Space>
                            <Button type="primary">Lưu</Button>
                            <Button icon={<PrinterOutlined />}>In</Button>
                            <Button type="primary" style={{ background: '#52c41a' }}>Đã trả</Button>
                        </Space>
                    </div>
                </div>
            )}

            {activeTab === 'payments' && (
                <div className={styles.paymentsTab}>
                    <Table
                        dataSource={data.payment_history || []}
                        columns={paymentColumns}
                        size="small"
                        pagination={false}
                        rowKey={(r, index) => r.id || r.receipt_code || `payment-${index}`}
                    />
                </div>
            )}

            {/* Edit Payment Receipt Modal */}
            <EditPaymentReceiptModal
                open={editReceiptOpen}
                receipt={selectedReceipt}
                returnData={data}
                onCancel={() => {
                    setEditReceiptOpen(false);
                    setSelectedReceipt(null);
                }}
                onSuccess={() => {
                    setEditReceiptOpen(false);
                    setSelectedReceipt(null);
                }}
            />
        </div>
    );
};

export default ReturnDetailPanel;
