import React, { useState } from 'react';
import { Modal, Select, Table, Button, Empty } from 'antd';
import { WifiOutlined, InboxOutlined } from '@ant-design/icons';
import styles from './SyncDataModal.module.css';

const SyncDataModal = ({ open, onClose, onSyncAll }) => {
    const [docType, setDocType] = useState('all');

    const columns = [
        {
            title: 'Loại phiếu',
            dataIndex: 'type',
            key: 'type',
        },
        {
            title: 'Mã phiếu',
            dataIndex: 'code',
            key: 'code',
        },
        {
            title: 'Thời gian',
            dataIndex: 'time',
            key: 'time',
            sorter: true,
        },
        {
            title: 'Giá trị',
            dataIndex: 'value',
            key: 'value',
        },
    ];

    // Empty data for demonstration
    const data = [];

    return (
        <Modal
            title="Đồng bộ phiếu"
            open={open}
            onCancel={onClose}
            footer={null}
            width={700}
            className={styles.modal}
        >
            <div className={styles.header}>
                <div className={styles.filterRow}>
                    <span className={styles.label}>Loại phiếu</span>
                    <Select
                        value={docType}
                        onChange={setDocType}
                        options={[
                            { value: 'all', label: '--Tất cả--' },
                            { value: 'invoice', label: 'Hóa đơn' },
                            { value: 'order', label: 'Đặt hàng' },
                            { value: 'return', label: 'Trả hàng' },
                        ]}
                        className={styles.select}
                    />
                </div>
                <div className={styles.connectionStatus}>
                    <span className={styles.label}>Trạng thái kết nối:</span>
                    <span className={styles.connected}>
                        Có Internet <WifiOutlined />
                    </span>
                </div>
            </div>

            <Table
                columns={columns}
                dataSource={data}
                rowKey="id"
                pagination={false}
                size="small"
                locale={{
                    emptyText: (
                        <Empty
                            image={<InboxOutlined className={styles.emptyIcon} />}
                            description="Không tìm thấy kết quả nào phù hợp"
                        />
                    ),
                }}
            />

            <div className={styles.footer}>
                <Button type="primary" onClick={onSyncAll} className={styles.syncBtn}>
                    Đồng bộ tất cả
                </Button>
            </div>
        </Modal>
    );
};

export default SyncDataModal;
