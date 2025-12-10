import React, { useEffect, useState } from 'react';
import { Modal, Descriptions, Table, Tag, Button, Space, Spin, Typography, Divider } from 'antd';
import { ExportOutlined, CopyOutlined, SaveOutlined, RollbackOutlined, PrinterOutlined } from '@ant-design/icons';
import { useNavigate } from 'react-router-dom';
import dayjs from 'dayjs';
import invoiceApi from '../../api/invoiceApi';
import styles from './InvoiceDetailModal.module.css';

const { Text } = Typography;

const currency = (v) => (Number(v) || 0).toLocaleString('vi-VN');

const statusColors = {
    completed: 'success',
    COMPLETED: 'success',
    DELIVERED: 'success',
    draft: 'default',
    DRAFT: 'default',
    pending: 'processing',
    PENDING: 'processing',
    cancelled: 'error',
    CANCELLED: 'error',
};

const statusLabels = {
    completed: 'Hoàn thành',
    COMPLETED: 'Hoàn thành',
    DELIVERED: 'Đã giao',
    draft: 'Nháp',
    DRAFT: 'Nháp',
    pending: 'Đang xử lý',
    PENDING: 'Đang xử lý',
    cancelled: 'Đã hủy',
    CANCELLED: 'Đã hủy',
};

const InvoiceDetailModal = ({ open, invoiceCode, onClose }) => {
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);
    const [invoice, setInvoice] = useState(null);

    useEffect(() => {
        if (!open || !invoiceCode) {
            setInvoice(null);
            return;
        }
        const fetchInvoice = async () => {
            setLoading(true);
            try {
                const res = await invoiceApi.getInvoiceByCode(invoiceCode);
                if (res.success && res.data) {
                    setInvoice(res.data);
                } else {
                    setInvoice(null);
                }
            } catch (e) {
                console.error('Failed to fetch invoice:', e);
                setInvoice(null);
            } finally {
                setLoading(false);
            }
        };
        fetchInvoice();
    }, [open, invoiceCode]);

    const handleOpenInvoicePage = () => {
        onClose();
        navigate(`/orders/invoices?search=${invoiceCode}`);
    };

    const itemColumns = [
        { title: 'Mã hàng', dataIndex: 'product_code', key: 'product_code', width: 100 },
        { title: 'Tên hàng', dataIndex: 'product_name', key: 'product_name' },
        { title: 'Số lượng', dataIndex: 'quantity', key: 'quantity', width: 80, align: 'right' },
        { title: 'Đơn giá', dataIndex: 'unit_price', key: 'unit_price', width: 100, align: 'right', render: currency },
        { title: 'Giảm giá', dataIndex: 'discount', key: 'discount', width: 90, align: 'right', render: (v) => currency(v || 0) },
        { title: 'Giá bán', dataIndex: 'selling_price', key: 'selling_price', width: 100, align: 'right', render: (v, r) => currency(r.unit_price - (r.discount || 0)) },
        { title: 'Thành tiền', dataIndex: 'line_total', key: 'line_total', width: 110, align: 'right', render: currency },
    ];

    const status = invoice?.invoice_status || invoice?.status || '';
    const items = invoice?.items || invoice?.line_items || [];

    return (
        <Modal
            title="Hóa đơn"
            open={open}
            onCancel={onClose}
            width={800}
            footer={
                <Space>
                    <Button icon={<CopyOutlined />}>Sao chép</Button>
                    <Button icon={<ExportOutlined />}>Xuất file</Button>
                    <Button icon={<SaveOutlined />}>Lưu</Button>
                    <Button icon={<RollbackOutlined />}>Trả hàng</Button>
                    <Button icon={<PrinterOutlined />}>In</Button>
                    <Button type="primary" onClick={handleOpenInvoicePage}>Mở phiếu</Button>
                </Space>
            }
        >
            <Spin spinning={loading}>
                {invoice ? (
                    <div className={styles.content}>
                        {/* Header */}
                        <div className={styles.header}>
                            <Space>
                                <Text strong>{invoice.customer_name || 'Khách lẻ'}</Text>
                                <a onClick={handleOpenInvoicePage}>{invoice.invoice_code || invoiceCode}</a>
                                <Tag color={statusColors[status]}>{statusLabels[status] || status}</Tag>
                            </Space>
                            <Text type="secondary">{invoice.branch_name || '—'}</Text>
                        </div>

                        {/* Info row */}
                        <Descriptions size="small" column={3} className={styles.info}>
                            <Descriptions.Item label="Người tạo">{invoice.creator_name || '—'}</Descriptions.Item>
                            <Descriptions.Item label="Người bán">{invoice.seller_name || '—'}</Descriptions.Item>
                            <Descriptions.Item label="Ngày bán">{invoice.issued_at ? dayjs(invoice.issued_at).format('DD/MM/YYYY HH:mm') : '—'}</Descriptions.Item>
                            <Descriptions.Item label="Kênh bán">{invoice.sales_channel || 'Bán trực tiếp'}</Descriptions.Item>
                            <Descriptions.Item label="Bảng giá">{invoice.price_list_name || 'Sales'}</Descriptions.Item>
                        </Descriptions>

                        {/* Items table */}
                        <Table
                            dataSource={items}
                            columns={itemColumns}
                            pagination={false}
                            size="small"
                            rowKey={(r, i) => r.id || i}
                            className={styles.itemsTable}
                        />

                        {/* Totals */}
                        <div className={styles.totals}>
                            <div className={styles.totalRow}>
                                <span>Tổng tiền hàng ({items.length})</span>
                                <span>{currency(invoice.subtotal || invoice.total)}</span>
                            </div>
                            <div className={styles.totalRow}>
                                <span>Giảm giá hóa đơn</span>
                                <span>{currency(invoice.discount_amount || 0)}</span>
                            </div>
                            <div className={styles.totalRow}>
                                <span>Khách cần trả</span>
                                <span>{currency(invoice.customer_payable || invoice.total)}</span>
                            </div>
                            <div className={styles.totalRow}>
                                <Text strong>Khách đã trả</Text>
                                <Text strong>{currency(invoice.customer_paid || 0)}</Text>
                            </div>
                        </div>

                        <Divider style={{ margin: '12px 0' }} />
                        <Text type="secondary" italic>{invoice.note || 'Chưa có ghi chú'}</Text>
                    </div>
                ) : (
                    !loading && <div>Không tìm thấy hóa đơn</div>
                )}
            </Spin>
        </Modal>
    );
};

export default InvoiceDetailModal;
