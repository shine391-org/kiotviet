import React, { useState } from 'react';
import { Modal, Input, DatePicker, Table, Button, Pagination } from 'antd';
import styles from './ReturnInvoiceModal.module.css';

// Mock data
const MOCK_INVOICES = [
    { id: 1, code: 'HD031593', time: '06/12/2025 11:31', staff: 'nhung', customer: 'a Việt', total: 940000 },
    { id: 2, code: 'HD031590', time: '03/12/2025 14:42', staff: 'Chị Phương Anh', customer: 'C.Diệp', total: 1280000 },
    { id: 3, code: 'HDO1764734397896', time: '03/12/2025 10:59', staff: 'nhung', customer: 'Khách lẻ', total: 800000 },
    { id: 4, code: 'HD031586', time: '01/12/2025 19:39', staff: 'Chị Phương Anh', customer: 'anh Hà', total: 1380000 },
    { id: 5, code: 'HD031585', time: '01/12/2025 14:16', staff: 'Chị Phương Anh', customer: 'a Tình', total: 2050000 },
    { id: 6, code: 'HD031584', time: '01/12/2025 14:05', staff: 'Chị Phương Anh', customer: 'Trần Nguyễn', total: 1650000 },
    { id: 7, code: 'HD031583', time: '01/12/2025 14:04', staff: 'Chị Phương Anh', customer: 'nguyễn hải thăng', total: 2050000 },
];

const ReturnInvoiceModal = ({ open, onClose, onSelect, onQuickReturn }) => {
    const [searchType, setSearchType] = useState('code');
    const [fromDate, setFromDate] = useState(null);
    const [toDate, setToDate] = useState(null);
    const [currentPage, setCurrentPage] = useState(1);

    const searchOptions = [
        { key: 'code', label: 'Theo mã hóa đơn' },
        { key: 'shipping', label: 'Theo mã vận đơn bán' },
        { key: 'customer', label: 'Theo khách hàng hoặc ĐT' },
        { key: 'productCode', label: 'Theo mã hàng' },
        { key: 'productName', label: 'Theo tên hàng' },
    ];

    const columns = [
        {
            title: 'Mã hóa đơn',
            dataIndex: 'code',
            key: 'code',
            render: (code) => <a className={styles.linkCode}>{code}</a>,
        },
        {
            title: 'Thời gian',
            dataIndex: 'time',
            key: 'time',
            sorter: true,
        },
        {
            title: 'Nhân viên',
            dataIndex: 'staff',
            key: 'staff',
        },
        {
            title: 'Khách hàng',
            dataIndex: 'customer',
            key: 'customer',
        },
        {
            title: 'Tổng cộng',
            dataIndex: 'total',
            key: 'total',
            align: 'right',
            render: (val) => val.toLocaleString('vi-VN'),
        },
        {
            title: '',
            key: 'action',
            render: (_, record) => (
                <Button onClick={() => onSelect?.(record)}>Chọn</Button>
            ),
        },
    ];

    return (
        <Modal
            title="Chọn hóa đơn trả hàng"
            open={open}
            onCancel={onClose}
            footer={null}
            width={950}
            className={styles.modal}
        >
            <div className={styles.content}>
                {/* Left Sidebar */}
                <div className={styles.sidebar}>
                    <div className={styles.searchSection}>
                        <h4>Tìm kiếm</h4>
                        <Input placeholder="Theo mã hóa đơn" className={styles.searchInput} />
                        {searchOptions.map((opt) => (
                            <div
                                key={opt.key}
                                className={`${styles.searchOption} ${searchType === opt.key ? styles.active : ''}`}
                                onClick={() => setSearchType(opt.key)}
                            >
                                {opt.label}
                            </div>
                        ))}
                    </div>

                    <div className={styles.dateSection}>
                        <h4>Thời gian</h4>
                        <DatePicker
                            placeholder="Từ ngày"
                            value={fromDate}
                            onChange={setFromDate}
                            className={styles.datePicker}
                        />
                        <DatePicker
                            placeholder="Đến ngày"
                            value={toDate}
                            onChange={setToDate}
                            className={styles.datePicker}
                        />
                    </div>
                </div>

                {/* Main Content */}
                <div className={styles.mainContent}>
                    <Table
                        columns={columns}
                        dataSource={MOCK_INVOICES}
                        rowKey="id"
                        pagination={false}
                        size="small"
                    />

                    <div className={styles.footer}>
                        <Pagination
                            current={currentPage}
                            total={693}
                            pageSize={7}
                            onChange={setCurrentPage}
                            showSizeChanger={false}
                            size="small"
                        />
                        <span className={styles.totalText}>Hiển thị 1 - 7 trên tổng số 693 hóa đơn</span>
                        <Button type="primary" onClick={onQuickReturn} className={styles.quickReturnBtn}>
                            Trả nhanh
                        </Button>
                    </div>
                </div>
            </div>
        </Modal>
    );
};

export default ReturnInvoiceModal;
