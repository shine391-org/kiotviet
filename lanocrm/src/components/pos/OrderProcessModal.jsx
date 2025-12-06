import React, { useState } from 'react';
import { Modal, Input, DatePicker, Table, Button } from 'antd';
import { SearchOutlined } from '@ant-design/icons';
import styles from './OrderProcessModal.module.css';

// Mock data
const MOCK_ORDERS = [
    {
        id: 1,
        code: 'DH000044',
        time: '19/11/2025 08:07',
        customer: 'Khách lẻ',
        total: 9000000,
        status: 'Phiếu tạm',
        note: '',
    },
];

const OrderProcessModal = ({ open, onClose, onSelect }) => {
    const [searchType, setSearchType] = useState('code');
    const [searchValue, setSearchValue] = useState('');
    const [fromDate, setFromDate] = useState(null);
    const [toDate, setToDate] = useState(null);

    const searchOptions = [
        { key: 'code', label: 'Theo mã đặt hàng' },
        { key: 'customer', label: 'Theo khách hàng' },
        { key: 'note', label: 'Theo ghi chú đặt hàng' },
        { key: 'productCode', label: 'Theo mã hàng' },
        { key: 'productName', label: 'Theo tên hàng' },
    ];

    const columns = [
        {
            title: 'Mã đặt hàng',
            dataIndex: 'code',
            key: 'code',
            render: (code) => <a className={styles.linkCode}>{code}</a>,
        },
        {
            title: 'Thời gian',
            dataIndex: 'time',
            key: 'time',
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
            render: (val) => val.toLocaleString('vi-VN'),
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            key: 'status',
        },
        {
            title: 'Ghi chú',
            dataIndex: 'note',
            key: 'note',
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
            title="Xử lý đặt hàng"
            open={open}
            onCancel={onClose}
            footer={null}
            width={900}
            className={styles.modal}
        >
            <div className={styles.content}>
                {/* Left Sidebar - Search Options */}
                <div className={styles.sidebar}>
                    <div className={styles.searchSection}>
                        <h4>Tìm kiếm</h4>
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

                {/* Main Content - Table */}
                <div className={styles.mainContent}>
                    <Table
                        columns={columns}
                        dataSource={MOCK_ORDERS}
                        rowKey="id"
                        pagination={false}
                        size="small"
                    />
                </div>
            </div>
        </Modal>
    );
};

export default OrderProcessModal;
