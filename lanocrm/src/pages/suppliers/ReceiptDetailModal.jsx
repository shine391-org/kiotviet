// src/pages/suppliers/ReceiptDetailModal.jsx

import React from 'react';
import {
    Modal,
    Table,
    Tag,
    Button,
    Input,
} from 'antd';
import { FileTextOutlined } from '@ant-design/icons';
import styles from './ReceiptDetailModal.module.css';

/**
 * ReceiptDetailModal - Modal showing receipt/purchase order details
 * @agent-layer: frontend-component
 * @agent-pattern: detail-modal
 */

const currency = (value) => {
    if (value === null || value === undefined) return '0';
    return new Intl.NumberFormat('vi-VN').format(value);
};

// Mock data for receipt details
const mockReceiptData = {
    code: 'PN002300',
    status: 'Đã nhập hàng',
    branch: 'Lano - HN',
    creator: '01666100999',
    receiver: '01666100999',
    date: '26/11/2023 15:13',
    supplierName: 'mainam',
    items: [
        {
            id: 1,
            productCode: 'MKTL02',
            productName: 'Mặt khóa thật lưng bản 3.5 hàng MKTL02',
            quantity: 6,
            unitPrice: 210000,
            discount: 0,
            importPrice: 210000,
            total: 1260000,
        },
        {
            id: 2,
            productCode: 'MKTL01',
            productName: 'Mặt khóa kim dây thật lưng bản 3.5 MKTL01',
            quantity: 2,
            unitPrice: 180000,
            discount: 0,
            importPrice: 180000,
            total: 360000,
        },
    ],
    totalItems: 2,
    subtotal: 1620000,
    discount: 0,
    grandTotal: 1620000,
    paid: 1620000,
};

const ReceiptDetailModal = ({ open, onCancel, receiptCode }) => {
    // TODO: Fetch receipt data by receiptCode from API
    // For now using mock data
    const receipt = { ...mockReceiptData, code: receiptCode || mockReceiptData.code };

    const columns = [
        {
            title: 'Mã hàng',
            dataIndex: 'productCode',
            key: 'productCode',
            width: 100,
            render: (text) => (
                <a href="#" onClick={(e) => e.preventDefault()} style={{ color: '#1890ff' }}>
                    {text}
                </a>
            ),
        },
        {
            title: 'Tên hàng',
            dataIndex: 'productName',
            key: 'productName',
        },
        {
            title: 'Số lượng',
            dataIndex: 'quantity',
            key: 'quantity',
            align: 'center',
            width: 80,
        },
        {
            title: 'Đơn giá',
            dataIndex: 'unitPrice',
            key: 'unitPrice',
            align: 'right',
            width: 100,
            render: (v) => currency(v),
        },
        {
            title: 'Giảm giá',
            dataIndex: 'discount',
            key: 'discount',
            align: 'right',
            width: 80,
            render: (v) => currency(v),
        },
        {
            title: 'Giá nhập',
            dataIndex: 'importPrice',
            key: 'importPrice',
            align: 'right',
            width: 100,
            render: (v) => currency(v),
        },
        {
            title: 'Thành tiền',
            dataIndex: 'total',
            key: 'total',
            align: 'right',
            width: 120,
            render: (v) => <strong style={{ color: '#1890ff' }}>{currency(v)}</strong>,
        },
    ];

    return (
        <Modal
            title="Phiếu nhập hàng"
            open={open}
            onCancel={onCancel}
            width={900}
            footer={
                <Button type="primary" icon={<FileTextOutlined />}>
                    Mở phiếu
                </Button>
            }
        >
            {/* Header Info */}
            <div className={styles.header}>
                <div className={styles.headerLeft}>
                    <span className={styles.receiptCode}>{receipt.code}</span>
                    <Tag color="success">{receipt.status}</Tag>
                </div>
                <span className={styles.branchTag}>{receipt.branch}</span>
            </div>

            {/* Meta Info */}
            <div className={styles.metaGrid}>
                <div className={styles.metaItem}>
                    <span className={styles.metaLabel}>Người tạo:</span>
                    <span>{receipt.creator}</span>
                </div>
                <div className={styles.metaItem}>
                    <span className={styles.metaLabel}>Người nhập:</span>
                    <span>{receipt.receiver}</span>
                </div>
                <div className={styles.metaItem}>
                    <span className={styles.metaLabel}>Ngày nhập:</span>
                    <span>{receipt.date}</span>
                </div>
            </div>

            <div className={styles.supplierLink}>
                <span className={styles.metaLabel}>Tên NCC:</span>
                <a href="#" onClick={(e) => e.preventDefault()} style={{ color: '#1890ff' }}>
                    {receipt.supplierName}
                </a>
            </div>

            {/* Product Table */}
            <div className={styles.tableSection}>
                <div className={styles.tableHeader}>
                    <Input placeholder="Tìm mã hàng" style={{ width: 120 }} size="small" />
                    <Input placeholder="Tìm tên hàng" style={{ width: 200, marginLeft: 8 }} size="small" />
                </div>
                <Table
                    columns={columns}
                    dataSource={receipt.items}
                    rowKey="id"
                    pagination={false}
                    size="small"
                />
            </div>

            {/* Summary */}
            <div className={styles.summary}>
                <div className={styles.summaryRow}>
                    <span>Số lượng mặt hàng</span>
                    <span>{receipt.totalItems}</span>
                </div>
                <div className={styles.summaryRow}>
                    <span>Tổng tiền hàng ({receipt.items.reduce((sum, i) => sum + i.quantity, 0)})</span>
                    <span>{currency(receipt.subtotal)}</span>
                </div>
                <div className={styles.summaryRow}>
                    <span>Giảm giá ⓘ</span>
                    <span>{currency(receipt.discount)}</span>
                </div>
                <div className={styles.summaryRow}>
                    <span><strong>Tổng cộng</strong></span>
                    <span><strong>{currency(receipt.grandTotal)}</strong></span>
                </div>
                <div className={styles.summaryRow}>
                    <span>Tiền đã trả NCC</span>
                    <span>{currency(receipt.paid)}</span>
                </div>
            </div>

            {/* Notes */}
            <div className={styles.notesSection}>
                <FileTextOutlined style={{ marginRight: 8 }} />
                <span>Ghi chú...</span>
            </div>
        </Modal>
    );
};

export default ReceiptDetailModal;
